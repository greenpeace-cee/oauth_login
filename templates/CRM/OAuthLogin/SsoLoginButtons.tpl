{* SSO Login Button — placed below the Angular login form via JS.
   The .oauth-sso-section--fallback block is rendered hidden and only shown
   if the Angular form never appears (e.g. JS disabled, render error). *}
<div class="crm-section oauthlogin-section">
  {foreach from=$oauthLoginLinks item=oauthLoginLink}
    <a href="{$oauthLoginLink.url}" class="btn crm-button oauth-sso-button"><i class="crm-i fa-sign-in"></i> {$oauthLoginLink.title}</a>
  {/foreach}
</div>
<script>
(function () {
  if (window.__oauthSsoButtonInjected) return;
  window.__oauthSsoButtonInjected = true;
  var injected = false;

  function inject() {
    if (injected) {
      return true; // already injected
    }
    // Target the form-state .crm-login (the one without -loading/-already-logged-in modifiers).
    var form = document.querySelector('.crm-login:not(.crm-login-loading):not(.crm-login-already-logged-in) form');
    if (!form) return false;

    var element = cj('.oauthlogin-section').detach();
    cj('.standalone-auth-box').append(element);
    injected = true;
  }

  inject();
  
  if (!injected) {
    // Watch for the Angular form to render.
    var settled = false;
    var observer = new MutationObserver(function () {
      if (settled) return;
      inject()
      if (injected) {
        settled = true;
        observer.disconnect();
      }
    });
    observer.observe(document.body, { childList: true, subtree: true });

    // Final timeout — reveal the fallback if Angular never finishes.
    setTimeout(function () {
      if (settled) return;
      observer.disconnect();
    }, 5000);
  }
})();
</script>