{crmScope extensionKey='oauth_login'}
{foreach from=$claimfields item=claimfield}
{include file="CRM/OAuthLogin/Form/Claim.tpl" claimfield=$claimfield}
{/foreach}
{/crmScope}