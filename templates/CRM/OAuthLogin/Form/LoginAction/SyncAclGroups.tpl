{crmScope extensionKey='oauth_login'}
<div class="crm-section">
  <div class="label">{$form.claim.label}</div>
  <div class="content">{$form.claim.html}&nbsp;{ts 1=$form.token.html}from %1 token{/ts}</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.limit_to_groups.label}</div>
  <div class="content">{$form.limit_to_groups.html}
    <p class="description">{ts}No means synchronize every CiviCRM ACL Group if a user has these groups in their claim.{/ts}
  </div>
  <div class="clear"></div>
</div>
<div class="crm-section" id="selectedGroupSection">
  <div class="label">{$form.selected_groups.label}</div>
  <div class="content">{$form.selected_groups.html}</div>
  <div class="clear"></div>
</div>
  <script type="text/javascript">{literal}
  CRM.$(function($) {
    const limitRadios = document.querySelectorAll('input[name="limit_to_groups"]');
 
    // Define the event handler function
    function hideShowGroups() {
      $('#selectedGroupSection').addClass('hiddenElement');
      if (document.querySelectorAll('input[name="limit_to_groups"]:checked').length) {
        const limit = document.querySelectorAll('input[name="limit_to_groups"]:checked')[0].value; 
        console.log(limit);
        if (limit > 0) {
          $('#selectedGroupSection').removeClass('hiddenElement');
        }
      } 
    }
 
    // Attach the "change" event listener to each radio button
    limitRadios.forEach(radio => {
      radio.addEventListener("change", hideShowGroups);
    });

    hideShowGroups();
  });
  {/literal}</script>
{/crmScope}