<?php

namespace HimeNihongo\KanjiPlugin\Supports;

use Bojaghi\Contract\Support;

class RoleSupport implements Support
{
    public const string ROLE_EDITOR = 'hnkp_editor';

    public function createRole(): self
    {
        $role = get_role(self::ROLE_EDITOR);

        if (!$role) {
            add_role(self::ROLE_EDITOR, '한자 편집자', self::getCustomEditorCaps());
        }

        return $this;
    }

    public function assignCapsTo(string $role): self
    {
        get_role($role)?->add_cap(self::ROLE_EDITOR);

        return $this;
    }

    public function removeRole(): self
    {
        $role = get_role(self::ROLE_EDITOR);

        if ($role) {
            remove_role(self::ROLE_EDITOR);
        }

        return $this;
    }

    public function revokeCapsFrom(string $role): self
    {
        get_role($role)?->remove_cap(self::ROLE_EDITOR);

        return $this;
    }

    public function getCustomEditorCaps(): array
    {
        return [
            'read', // 기본적으로 대시보드에 접근할 수 있는 권한
        ];
    }
}
