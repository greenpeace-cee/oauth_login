{crmScope extensionKey='oauth_login'}
<div class="crm-section">
  <div class="label">{$form.claim.label}</div>
  <div class="content">{$form.claim.html}&nbsp;{ts 1=$form.token.html}from %1 token{/ts}</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.limit_to_roles.label}</div>
  <div class="content">{$form.limit_to_roles.html}</div>
  <div class="clear"></div>
</div>
<div class="crm-section" id="selectedRolesSection">
  <div class="label">{$form.selected_roles.label}</div>
  <div class="content">{$form.selected_roles.html}</div>
  <div class="clear"></div>
</div>
  <script type="text/javascript">{literal}
  CRM.$(function($) {
    const limitRadios = document.querySelectorAll('input[name="limit_to_roles"]');
 
    // Define the event handler function
    function hideShowRoles() {
      $('#selectedRolesSection').addClass('hiddenElement');
      if (document.querySelectorAll('input[name="limit_to_roles"]:checked').length) {
        const limit = document.querySelectorAll('input[name="limit_to_roles"]:checked')[0].value; 
        console.log(limit);
        if (limit > 0) {
          $('#selectedRolesSection').removeClass('hiddenElement');
        }
      } 
    }
 
    // Attach the "change" event listener to each radio button
    limitRadios.forEach(radio => {
      radio.addEventListener("change", hideShowRoles);
    });

    hideShowRoles();
  });
  {/literal}</script>
{/crmScope}