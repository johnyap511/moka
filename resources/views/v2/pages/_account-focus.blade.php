{{-- Account pages: hide the chat widget while a field is focused on phones. --}}
<script>
(function () {
    var card = document.querySelector('.acct-card');
    if (!card) return;
    card.addEventListener('focusin', function (e) { if (e.target.matches('input')) document.documentElement.classList.add('acct-typing'); });
    card.addEventListener('focusout', function () { setTimeout(function () { if (!card.contains(document.activeElement) || !document.activeElement.matches('input')) document.documentElement.classList.remove('acct-typing'); }, 50); });
})();
</script>
