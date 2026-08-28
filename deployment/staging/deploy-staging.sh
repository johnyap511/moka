#!/usr/bin/env bash
# =============================================================================
# MOKA — Staging Deploy Script
#
# Usage:
#   ./deploy-staging.sh [OPTIONS]
#
# Options:
#   --skip-migrate    Skip running database migrations
#   --no-cache        Build Docker images with --no-cache flag
#   --branch BRANCH   Override git branch to deploy (default: staging)
#   -h, --help        Show this help message
#
# Prerequisites (on your LOCAL machine running this script):
#   - gcloud CLI authenticated: gcloud auth login
#   - SSH key added to the GCP VM (via GCP Console → Metadata → SSH Keys)
#   - STAGING_VM_NAME, STAGING_VM_ZONE, STAGING_PROJECT set below
# =============================================================================

set -euo pipefail
IFS=$'\n\t'

# ---------------------------------------------------------------------------
# Configuration — edit these to match your GCP setup
# ---------------------------------------------------------------------------
readonly STAGING_PROJECT="${STAGING_PROJECT:-YOUR_GCP_PROJECT_ID}"
readonly STAGING_VM_NAME="${STAGING_VM_NAME:-moka-staging-vm}"
readonly STAGING_VM_ZONE="${STAGING_VM_ZONE:-asia-southeast1-b}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-staging}"
readonly APP_DIR="${APP_DIR:-/opt/moka}"
readonly USE_IAP="${USE_IAP:-false}"
readonly COMPOSE_FILE="deployment/staging/docker-compose.yml"
readonly ENV_FILE="deployment/staging/.env.staging"

# ---------------------------------------------------------------------------
# Parse arguments
# ---------------------------------------------------------------------------
SKIP_MIGRATE=false
NO_CACHE_FLAG=""

while [[ $# -gt 0 ]]; do
    case "$1" in
        --skip-migrate) SKIP_MIGRATE=true;  shift ;;
        --no-cache)     NO_CACHE_FLAG="--no-cache"; shift ;;
        --branch)       DEPLOY_BRANCH="$2"; shift 2 ;;
        -h|--help)
            grep '^#' "$0" | grep -v '#!/' | sed 's/^# \?//'
            exit 0
            ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
done

# ---------------------------------------------------------------------------
# Color helpers
# ---------------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
RESET='\033[0m'

log_info()    { echo -e "${CYAN}[INFO]${RESET}  $*"; }
log_success() { echo -e "${GREEN}[OK]${RESET}    $*"; }
log_warn()    { echo -e "${YELLOW}[WARN]${RESET}  $*"; }
log_error()   { echo -e "${RED}[ERROR]${RESET} $*" >&2; }
log_step()    { echo -e "\n${BOLD}${CYAN}==> $*${RESET}"; }

# Timestamp for log lines
export PS4='+ $(date "+%H:%M:%S") '

# ---------------------------------------------------------------------------
# Pre-flight checks
# ---------------------------------------------------------------------------
log_step "Pre-flight checks"

if [[ "$STAGING_PROJECT" == "YOUR_GCP_PROJECT_ID" ]]; then
    log_error "STAGING_PROJECT is not set. Edit the script or export the env var:"
    log_error "  export STAGING_PROJECT=my-gcp-project"
    exit 1
fi

for cmd in gcloud ssh; do
    if ! command -v "$cmd" &>/dev/null; then
        log_error "Required command not found: $cmd"
        exit 1
    fi
done
log_success "gcloud and ssh are available"

# Verify gcloud is authenticated
if ! gcloud auth print-access-token &>/dev/null; then
    log_error "gcloud is not authenticated. Run: gcloud auth login"
    exit 1
fi
log_success "gcloud is authenticated"

# ---------------------------------------------------------------------------
# Resolve the VM's external IP (used for final URL output)
# ---------------------------------------------------------------------------
log_step "Resolving staging VM external IP"
EXTERNAL_IP=$(gcloud compute instances describe "$STAGING_VM_NAME" \
    --zone="$STAGING_VM_ZONE" \
    --project="$STAGING_PROJECT" \
    --format="get(networkInterfaces[0].accessConfigs[0].natIP)" 2>/dev/null || echo "")

if [[ -z "$EXTERNAL_IP" ]]; then
    log_warn "Could not resolve external IP automatically. Make sure the VM exists."
    log_warn "Deploy will continue — update APP_URL in .env.staging manually."
    EXTERNAL_IP="<UNKNOWN>"
else
    log_success "External IP: $EXTERNAL_IP"
fi

# ---------------------------------------------------------------------------
# Helper: run a command on the remote VM via gcloud ssh
# ---------------------------------------------------------------------------
remote() {
    local -a iap=()
    if [[ "$USE_IAP" == "true" ]]; then
        iap=(--tunnel-through-iap)
    fi
    gcloud compute ssh "$STAGING_VM_NAME" \
        --zone="$STAGING_VM_ZONE" \
        --project="$STAGING_PROJECT" \
        ${iap[@]+"${iap[@]}"} \
        --command="$1"
}

# ---------------------------------------------------------------------------
# Step 1: Pull latest code on the VM
# ---------------------------------------------------------------------------
log_step "Step 1/7 — Pulling latest code from branch '${DEPLOY_BRANCH}'"

remote "
    set -euo pipefail
    if [ ! -d '${APP_DIR}' ]; then
        echo 'App directory ${APP_DIR} not found. Run first-time setup first.'
        echo 'See STAGING_SETUP.md for instructions.'
        exit 1
    fi
    cd '${APP_DIR}'
    git fetch --all --prune
    git checkout '${DEPLOY_BRANCH}'
    git pull origin '${DEPLOY_BRANCH}'
    echo 'Git HEAD: '\$(git log --oneline -1)
"
log_success "Code updated to latest on branch '${DEPLOY_BRANCH}'"

# ---------------------------------------------------------------------------
# Step 2: Verify .env.staging is present on the VM
# ---------------------------------------------------------------------------
log_step "Step 2/7 — Verifying .env.staging on VM"

remote "
    set -euo pipefail
    ENV_PATH='${APP_DIR}/${ENV_FILE}'
    if [ ! -f \"\$ENV_PATH\" ]; then
        echo 'ERROR: \$ENV_PATH not found on VM.'
        echo 'Copy your .env.staging to the VM first:'
        echo '  gcloud compute scp deployment/staging/.env.staging ${STAGING_VM_NAME}:${APP_DIR}/${ENV_FILE} --zone=${STAGING_VM_ZONE}'
        exit 1
    fi
    # Warn if placeholder values are still present
    if grep -q 'REPLACE_WITH' \"\$ENV_PATH\"; then
        echo 'WARNING: .env.staging still contains placeholder values!'
    fi
    echo '.env.staging found.'
"
log_success ".env.staging verified"

# ---------------------------------------------------------------------------
# Step 3: Bring down existing containers
# ---------------------------------------------------------------------------
log_step "Step 3/7 — Taking down existing containers"

remote "
    set -euo pipefail
    cd '${APP_DIR}'
    docker compose -f '${COMPOSE_FILE}' down --remove-orphans || true
"
log_success "Existing containers stopped"

# ---------------------------------------------------------------------------
# Step 4: Build fresh Docker images
# ---------------------------------------------------------------------------
log_step "Step 4/7 — Building Docker images ${NO_CACHE_FLAG:+(--no-cache)}"

remote "
    set -euo pipefail
    cd '${APP_DIR}'
    docker compose -f '${COMPOSE_FILE}' build ${NO_CACHE_FLAG}
"
log_success "Docker images built successfully"

# ---------------------------------------------------------------------------
# Step 5: Start containers in detached mode
# ---------------------------------------------------------------------------
log_step "Step 5/7 — Starting containers"

remote "
    set -euo pipefail
    cd '${APP_DIR}'
    docker compose -f '${COMPOSE_FILE}' up -d
    # Wait for containers to be healthy
    echo 'Waiting for services to become healthy...'
    sleep 10
    docker compose -f '${COMPOSE_FILE}' ps
"
log_success "All containers started"

# ---------------------------------------------------------------------------
# Step 6: Run database migrations
# ---------------------------------------------------------------------------
if [[ "$SKIP_MIGRATE" == "false" ]]; then
    log_step "Step 6/7 — Running database migrations"

    remote "
        set -euo pipefail
        cd '${APP_DIR}'
        docker compose -f '${COMPOSE_FILE}' exec -T app \
            php artisan migrate --force --no-interaction
    "
    log_success "Migrations applied"
else
    log_warn "Step 6/7 — Migrations skipped (--skip-migrate)"
fi

# ---------------------------------------------------------------------------
# Step 7: Warm up Laravel caches
# ---------------------------------------------------------------------------
log_step "Step 7/7 — Warming Laravel caches"

remote "
    set -euo pipefail
    cd '${APP_DIR}'

    echo '  -> php artisan config:cache'
    docker compose -f '${COMPOSE_FILE}' exec -T app php artisan config:cache

    echo '  -> php artisan route:cache'
    docker compose -f '${COMPOSE_FILE}' exec -T app php artisan route:cache

    echo '  -> php artisan view:cache'
    docker compose -f '${COMPOSE_FILE}' exec -T app php artisan view:cache

    echo '  -> php artisan storage:link'
    docker compose -f '${COMPOSE_FILE}' exec -T app php artisan storage:link || true

    echo 'Caches warmed.'
"
log_success "Laravel caches warmed"

# ---------------------------------------------------------------------------
# Done — print staging URL
# ---------------------------------------------------------------------------
echo ""
echo -e "${GREEN}${BOLD}============================================================${RESET}"
echo -e "${GREEN}${BOLD}  MOKA Staging deployment complete!${RESET}"
echo -e "${GREEN}${BOLD}============================================================${RESET}"
echo ""
echo -e "  ${BOLD}Staging URL:${RESET}  ${CYAN}http://${EXTERNAL_IP}:8080${RESET}"
echo -e "  ${BOLD}Branch:${RESET}       ${DEPLOY_BRANCH}"
echo -e "  ${BOLD}VM:${RESET}           ${STAGING_VM_NAME} (${STAGING_VM_ZONE})"
echo ""
echo -e "  To tail logs:"
echo -e "  ${YELLOW}gcloud compute ssh ${STAGING_VM_NAME} --zone=${STAGING_VM_ZONE} -- 'cd ${APP_DIR} && docker compose -f ${COMPOSE_FILE} logs -f'${RESET}"
echo ""
