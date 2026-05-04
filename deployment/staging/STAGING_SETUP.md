# MOKA Staging Environment — Setup Guide

This guide walks through creating a staging deployment of MOKA on GCP from scratch.
The staging site is accessible at `http://<EXTERNAL_IP>:8080` — no domain or SSL needed.

---

## Table of Contents

1. [GCP VM Setup](#1-gcp-vm-setup)
2. [Install Docker on the VM](#2-install-docker-on-the-vm)
3. [First Deploy](#3-first-deploy)
4. [Accessing the Staging Site](#4-accessing-the-staging-site)
5. [SSH into the VM](#5-ssh-into-the-vm)
6. [Subsequent Deploys](#6-subsequent-deploys)
7. [Troubleshooting](#7-troubleshooting)

---

## 1. GCP VM Setup

### 1.1 Create the VM

Open [Google Cloud Console](https://console.cloud.google.com) → **Compute Engine** → **VM Instances** → **Create Instance**, or use the CLI:

```bash
gcloud compute instances create moka-staging-vm \
  --project=YOUR_GCP_PROJECT_ID \
  --zone=us-central1-a \
  --machine-type=e2-medium \
  --image-family=ubuntu-2204-lts \
  --image-project=ubuntu-os-cloud \
  --boot-disk-size=30GB \
  --boot-disk-type=pd-balanced \
  --tags=moka-staging \
  --metadata=enable-oslogin=true
```

**Recommended specs for staging:**

| Setting        | Value                      |
|----------------|----------------------------|
| Machine type   | `e2-medium` (2 vCPU, 4 GB) |
| OS             | Ubuntu 22.04 LTS            |
| Boot disk      | 30 GB SSD (`pd-balanced`)  |
| Region/Zone    | Match your production zone |

### 1.2 Open Firewall Port 8080

Create a firewall rule to allow inbound HTTP on port 8080:

```bash
gcloud compute firewall-rules create allow-moka-staging \
  --project=YOUR_GCP_PROJECT_ID \
  --direction=INGRESS \
  --priority=1000 \
  --network=default \
  --action=ALLOW \
  --rules=tcp:8080 \
  --source-ranges=0.0.0.0/0 \
  --target-tags=moka-staging \
  --description="Allow MOKA staging on port 8080"
```

> **Security tip:** Replace `0.0.0.0/0` with your office/VPN IP range to restrict staging access to your team only.

### 1.3 Reserve a Static External IP (Optional but recommended)

```bash
# Reserve a static IP so the address doesn't change on VM restart
gcloud compute addresses create moka-staging-ip \
  --project=YOUR_GCP_PROJECT_ID \
  --region=us-central1

# Assign it to the VM
gcloud compute instances add-access-config moka-staging-vm \
  --zone=us-central1-a \
  --access-config-name="External NAT" \
  --address=$(gcloud compute addresses describe moka-staging-ip --region=us-central1 --format="get(address)")
```

### 1.4 Get the External IP

```bash
gcloud compute instances describe moka-staging-vm \
  --zone=us-central1-a \
  --format="get(networkInterfaces[0].accessConfigs[0].natIP)"
```

Note this IP — it goes into `APP_URL` in `.env.staging`.

---

## 2. Install Docker on the VM

SSH into the VM (see [Section 5](#5-ssh-into-the-vm)), then run:

```bash
# Update package index
sudo apt-get update && sudo apt-get upgrade -y

# Install prerequisites
sudo apt-get install -y \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    git \
    unzip

# Add Docker's official GPG key
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
    | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

# Add Docker repository
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
  https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" \
  | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker Engine and Compose plugin
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Allow current user to run docker without sudo
sudo usermod -aG docker $USER
newgrp docker

# Verify
docker --version
docker compose version
```

---

## 3. First Deploy

### 3.1 Clone the repository on the VM

```bash
# Create app directory
sudo mkdir -p /opt/moka
sudo chown $USER:$USER /opt/moka

# Clone repo
git clone https://github.com/YOUR_ORG/moka.git /opt/moka
cd /opt/moka
git checkout claude/continue-previous-session-QztqY
```

### 3.2 Configure the staging environment

On the **VM**, create and populate `.env.staging`:

```bash
cp /opt/moka/deployment/staging/.env.staging /opt/moka/deployment/staging/.env.staging.local
```

Edit the file and fill in every `REPLACE_WITH_*` placeholder:

```bash
nano /opt/moka/deployment/staging/.env.staging
```

Key values to set:

| Variable          | Value                                      |
|-------------------|--------------------------------------------|
| `APP_KEY`         | Output of `php artisan key:generate --show` (run locally) |
| `APP_URL`         | `http://<YOUR_EXTERNAL_IP>:8080`           |
| `DB_PASSWORD`     | A strong random password                   |
| `DB_ROOT_PASSWORD`| A different strong random password         |
| `MAIL_USERNAME`   | Your Mailtrap credentials                  |
| `MAIL_PASSWORD`   | Your Mailtrap credentials                  |

> **Generate APP_KEY locally:**
> ```bash
> php artisan key:generate --show
> # Copy the base64:... output into APP_KEY in .env.staging
> ```

### 3.3 Run from your LOCAL machine

```bash
# Export your GCP project
export STAGING_PROJECT=YOUR_GCP_PROJECT_ID

# Make deploy script executable (already done if you pulled this repo)
chmod +x deployment/staging/deploy-staging.sh

# Deploy!
./deployment/staging/deploy-staging.sh
```

The script will:
- SSH into the VM via `gcloud compute ssh`
- Pull the latest code from the branch
- Build Docker images
- Start all services
- Run database migrations
- Warm Laravel caches
- Print the staging URL

---

## 4. Accessing the Staging Site

Once deployed, open a browser and navigate to:

```
http://<EXTERNAL_IP>:8080
```

Where `<EXTERNAL_IP>` is the VM's external IP from Step 1.4.

To find the IP at any time:

```bash
gcloud compute instances describe moka-staging-vm \
  --zone=us-central1-a \
  --format="get(networkInterfaces[0].accessConfigs[0].natIP)"
```

> The staging site intentionally has no SSL certificate.
> Chrome may show a "Not Secure" warning — this is expected for an IP-only staging environment.

---

## 5. SSH into the VM

### Via gcloud (recommended)

```bash
gcloud compute ssh moka-staging-vm \
  --zone=us-central1-a \
  --project=YOUR_GCP_PROJECT_ID
```

gcloud handles key management automatically.

### Via standard SSH (if you prefer)

```bash
# Add your public key to GCP metadata:
gcloud compute os-login ssh-keys add \
  --key-file=~/.ssh/id_rsa.pub \
  --project=YOUR_GCP_PROJECT_ID

# Then SSH directly using the external IP:
ssh -i ~/.ssh/id_rsa YOUR_USERNAME@<EXTERNAL_IP>
```

---

## 6. Subsequent Deploys

From your local machine:

```bash
# Standard redeploy (with migrations)
./deployment/staging/deploy-staging.sh

# Skip migrations (faster if no schema changes)
./deployment/staging/deploy-staging.sh --skip-migrate

# Force full rebuild (clears Docker layer cache)
./deployment/staging/deploy-staging.sh --no-cache

# Deploy a different branch
./deployment/staging/deploy-staging.sh --branch feature/my-branch
```

---

## 7. Troubleshooting

### Containers won't start

```bash
# On the VM:
cd /opt/moka
docker compose -f deployment/staging/docker-compose.yml logs
# Or for a specific service:
docker compose -f deployment/staging/docker-compose.yml logs app
docker compose -f deployment/staging/docker-compose.yml logs nginx
docker compose -f deployment/staging/docker-compose.yml logs db
```

### 502 Bad Gateway

The Nginx container is running but can't reach PHP-FPM.

```bash
# Check if app container is running
docker compose -f deployment/staging/docker-compose.yml ps

# Check app logs for PHP errors
docker compose -f deployment/staging/docker-compose.yml logs app

# Restart just the app container
docker compose -f deployment/staging/docker-compose.yml restart app
```

### Database connection refused

```bash
# Verify db container is healthy
docker compose -f deployment/staging/docker-compose.yml ps db

# Check DB logs
docker compose -f deployment/staging/docker-compose.yml logs db

# Test connection from app container
docker compose -f deployment/staging/docker-compose.yml exec app \
    php artisan db:show
```

### Port 8080 not accessible from browser

1. Check GCP firewall rule exists: **VPC Network → Firewall** in the Console
2. Verify the VM has the `moka-staging` network tag
3. Confirm Nginx is listening on 8080:
   ```bash
   docker compose -f deployment/staging/docker-compose.yml exec nginx \
       nginx -t
   ```

### Laravel storage permission errors

```bash
docker compose -f deployment/staging/docker-compose.yml exec app \
    bash -c "chown -R www-data:www-data storage bootstrap/cache && \
             chmod -R 755 storage bootstrap/cache"
```

### Clear all Laravel caches manually

```bash
cd /opt/moka
docker compose -f deployment/staging/docker-compose.yml exec app php artisan cache:clear
docker compose -f deployment/staging/docker-compose.yml exec app php artisan config:clear
docker compose -f deployment/staging/docker-compose.yml exec app php artisan route:clear
docker compose -f deployment/staging/docker-compose.yml exec app php artisan view:clear
```

### Full reset (nuclear option — deletes all data)

```bash
cd /opt/moka
docker compose -f deployment/staging/docker-compose.yml down -v --remove-orphans
docker compose -f deployment/staging/docker-compose.yml up -d --build
docker compose -f deployment/staging/docker-compose.yml exec app php artisan migrate --force
```

> Warning: `-v` removes named volumes including the database. All staging data will be lost.

---

## Reference — Useful Commands

```bash
# Tail live logs
docker compose -f deployment/staging/docker-compose.yml logs -f

# Open a shell in the app container
docker compose -f deployment/staging/docker-compose.yml exec app bash

# Run an Artisan command
docker compose -f deployment/staging/docker-compose.yml exec app php artisan <command>

# View resource usage
docker stats

# Check disk space
df -h
docker system df
```
