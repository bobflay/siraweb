<?php

namespace Xpertbot\Monitoring;

use Laravel\Nova\ResourceTool;

class Monitoring extends ResourceTool
{
    /**
     * Get the displayable name of the resource tool.
     *
     * @return string
     */
    public function name()
    {
        return 'Monitoring';
    }

    /**
     * Get the component name for the resource tool.
     *
     * @return string
     */
    public function component()
    {
        return 'monitoring';
    }
}
