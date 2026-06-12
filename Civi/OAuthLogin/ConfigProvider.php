<?php

namespace Civi\OAuthLogin;

class ConfigProvider {

  public const MODE_DISABLED = 'disabled';
  public const MODE_OPTIONAL = 'optional';
  public const MODE_REQUIRED = 'required';

  public function mode(): string {
    $mode = (string) $this->get('oauth_login_mode');
    return in_array($mode, [self::MODE_DISABLED, self::MODE_OPTIONAL, self::MODE_REQUIRED], TRUE)
      ? $mode
      : self::MODE_DISABLED;
  }

  public function isProvisioningEnabled(): bool {
    return $this->readBool('oauth_login_provisioning_enabled');
  }

  /**
   * @return string[] CiviCRM Role names to assign at provision time.
   */
  public function defaultRoles(): array {
    return $this->parseList('oauth_login_default_roles');
  }

  private function get(string $key): mixed {
    return \Civi::settings()->get($key);
  }

  /**
   * Splits a setting value on commas or whitespace.
   */
  private function parseList(string $key): array {
    $raw = $this->get($key);
    if (is_array($raw)) {
      return $raw;
    }
    $parts = preg_split('/[\s,]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return array_values(array_filter(array_map('trim', $parts)));
  }

  /**
   * Boolean coercion tolerant of env-sourced strings.
   */
  private function readBool(string $key): bool {
    $v = $this->get($key);
    if (is_bool($v)) {
      return $v;
    }
    if (is_int($v)) {
      return $v !== 0;
    }
    if ($v === NULL || $v === '') {
      return FALSE;
    }
    return in_array(strtolower((string) $v), ['1', 'true', 'yes', 'on'], TRUE);
  }



}