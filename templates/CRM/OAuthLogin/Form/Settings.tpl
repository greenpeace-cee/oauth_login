{crmScope extensionKey='oauth_login'}
{if (!$suppressForm)}
<div class="crm-block crm-form-block crm-setting-block crm-setting-block-{$settingPageName}">
{crmRegion name="crm-setting-form-$settingPageName-top"}
  {if !empty($readOnlyFields)}
    <div class="description">
      <i class="crm-i fa-lock" role="img" aria-hidden="true"></i>
      {ts}Some fields are loaded as 'readonly' as they have been set (overridden) in civicrm.settings.php.{/ts}
    </div>
  {/if}
{/crmRegion}
  {foreach from=$settingSections key="sectionName" item="section"}
    <div class="crm-setting-section crm-setting-section-{$sectionName}">
      {crmRegion name="crm-setting-$settingPageName-section-$sectionName"}
        {if !empty($section.title)}
          <h3>
            {if !empty($section.icon)}<i class="crm-i {$section.icon}" role="img" aria-hidden="true"></i>&nbsp;{/if}
            {$section.title|escape}
          </h3>
        {/if}
        {if !empty($section.description) || !empty($section.doc_url)}
          <div class="description">
            {if !empty($section.description)}{$section.description|escape}{/if}
            {if !empty($section.doc_url)}{docURL params=$section.doc_url}{/if}
          </div>
        {/if}
        <table class="form-layout-compressed">
          {foreach from=$section.fields key="setting_name" item="fieldSpec"}
            {if !empty($fieldSpec.template)}
              {include file=$fieldSpec.template}
            {else}
              {include file="CRM/Admin/Form/Setting/SettingField.tpl"}
            {/if}
          {/foreach}
        </table>
        {if $sectionName == 'contact_create'}
          <div id="create_contact_matcher_configuration">{if $create_contact_matcher_configuration_template !== NULL}{include file=$create_contact_matcher_configuration_template}{/if}</div>
        {/if}
        {if $sectionName == 'contact_update'}
          <div id="update_contact_matcher_configuration">{if $update_contact_matcher_configuration_template !== NULL}{include file=$update_contact_matcher_configuration_template}{/if}</div>
        {/if}
      {/crmRegion}
    </div>
  {/foreach}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
  <script type="text/javascript">{literal}
  CRM.$(function($) {
    $('#oauth_login_contact_create_matcher').on('change', function() {
      var createContactMatcher = $('#oauth_login_contact_create_matcher').val();
      if (createContactMatcher) {
        var dataUrl = CRM.url('civicrm/admin/setting/oauth_login', {'type': createContactMatcher, 'sectionName': 'contact_create'});
        CRM.loadPage(dataUrl, {'target': '#create_contact_matcher_configuration'});
      } else {
        $('#create_contact_matcher_configuration').empty();
      }
    });
    $('#oauth_login_contact_update_matcher').on('change', function() {
      var updateContactMatcher = $('#oauth_login_contact_update_matcher').val();
      if (updateContactMatcher) {
        var dataUrl = CRM.url('civicrm/admin/setting/oauth_login', {'type': updateContactMatcher, 'sectionName': 'contact_update'});
        CRM.loadPage(dataUrl, {'target': '#update_contact_matcher_configuration'});
      } else {
        $('#update_contact_matcher_configuration').empty();
      }
    });
  });
  {/literal}</script>
{elseif $sectionName == 'contact_create'}
  <div id="create_contact_matcher_configuration">{if $create_contact_matcher_configuration_template !== NULL}{include file=$create_contact_matcher_configuration_template}{/if}</div>
{elseif $sectionName == 'contact_update'}
  <div id="update_contact_matcher_configuration">{if $update_contact_matcher_configuration_template !== NULL}{include file=$update_contact_matcher_configuration_template}{/if}</div>
{/if}
{/crmScope}