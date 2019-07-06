<?php

namespace Sandeepchowdary7\Laraeventful\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * @see Sandeepchowdary7\Laraeventful\Eventful
 */
class LaraeventfulFacade extends Facade {
    protected static function getFacadeAccessor() { return 'Eventful'; }
}
