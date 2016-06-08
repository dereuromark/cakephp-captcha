<?php

namespace Captcha\Engine\Math;

interface MathInterface {

    /**
     * @return array
     */
    public function getExpression();

    /**
     * @return bool
     */
    public function getValue();

}
