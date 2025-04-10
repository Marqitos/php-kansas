<?php declare(strict_types = 1);
/**
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  */

namespace Kansas\Provider;

interface AuthMembershipInterface {

    public function validate(string $user, string $password): array|false;

    public function changePassword(Guid $id, $oldPassword, $newPassword): bool;
}
