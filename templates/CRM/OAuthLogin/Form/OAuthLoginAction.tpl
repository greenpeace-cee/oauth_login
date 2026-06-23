{crmScope extensionKey='oauth_login'}
{if $action eq 8}
  {* Are you sure to delete form *}
  <h3>{ts}Delete Login Action{/ts}</h3>
  <div class="crm-block crm-form-block">
    <div class="crm-section">{ts 1=$login_action.label}Are you sure to delete action '%1'?{/ts}</div>
  </div>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
{elseif (!$suppressForm)}
  <h3>{ts}Login Action{/ts}</h3>
  <div class="crm-block crm-form-block">
    <div class="crm-section">
      <div class="label">{$form.label.label}</div>
      <div class="content">{$form.label.html}</div>
      <div class="clear"></div>
    </div>
    <div class="crm-section">
      <div class="label">{$form.is_active.label}</div>
      <div class="content">{$form.is_active.html}</div>
      <div class="clear"></div>
    </div>
    <div class="crm-section">
      <div class="label">{$form.client_id.label}</div>
      <div class="content">{$form.client_id.html}</div>
      <div class="clear"></div>
    </div>
    <div class="crm-section">
        <div class="label">{$form.type.label}</div>
        <div class="content">{$form.type.html}</div>
        <div class="clear"></div>
    </div>
    <div id="type_configuration">
      {if $has_configuration}{include file=$configuration_template}{/if}
    </div>
  </div>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

  <script type="text/javascript">{literal}
  CRM.$(function($) {
    var id = {/literal}{if $id}{$id}{else}false{/if}{literal};
    $('#type').on('change', function() {
      var type = $('#type').val();
      if (type) {
        var dataUrl = CRM.url('civicrm/admin/oauth/oauthloginactions/form', {type: type, 'id': id});
        CRM.loadPage(dataUrl, {'target': '#type_configuration'});
      }
    });
  });
  {/literal}</script>
{else}
  <div id="type_configuration">{if $has_configuration}{include file=$configuration_template}{/if}</div>
{/if}
{/crmScope}