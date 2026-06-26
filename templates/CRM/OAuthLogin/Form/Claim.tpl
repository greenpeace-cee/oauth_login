{crmScope extensionKey='oauth_login'}
{capture assign='tokenfield'}{$claimfield}token{/capture}
<div class="crm-section">
  <div class="label">{$form.$claimfield.label}</div>
  <div class="content">{$form.$claimfield.html}&nbsp;{ts 1=$form.$tokenfield.html}from %1 token{/ts}</div>
  <div class="clear"></div>
</div>
{/crmScope}