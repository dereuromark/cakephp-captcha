<?php
/**
 * @var \Captcha\Model\Entity\Captcha $captcha
 */

if (is_resource($captcha->image)) {
    echo stream_get_contents($captcha->image);
} else {
    echo $captcha->image;
}
