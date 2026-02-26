<?php

namespace HimeNihongo\KanjiPlugin\Modules;

use Bojaghi\Contract\Module;
use HimeNihongo\KanjiPlugin\Supports\RoleSupport;

class ActivationDeactivation implements Module
{
    public function __construct()
    {
        register_activation_hook(HNKP_MAIN, [$this, 'activate']);
        register_deactivation_hook(HNKP_MAIN, [$this, 'deactivate']);
    }

    public function activate(): void
    {
        // Add role
        hnkp_get(RoleSupport::class)
            ?->createRole()
            ->assignCapsTo('administrator')
        ;
    }

    public function deactivate(): void
    {
        // Remove role
        hnkp_get(RoleSupport::class)
            ?->removeRole()
            ->revokeCapsFrom('administrator')
        ;
    }
}
