# Active captchas

The default MathEngine displays an addition or subtraction riddle. Complete the [stored challenge installation](../guide/installation#stored-challenges-and-admin-pages) first. Each form uses a UUID token bound to its session and IP address. Multiple open forms have independent tokens.

`NullEngine` provides an invisible stored token with timing checks.

## Forms with tables
Attach the behavior to the table in its `initialize()` method, or inside the controller action before creating or patching the entity:
```php
$this->MyTable->addBehavior('Captcha.Captcha');
```

You can also use the component (and the optional actions array) to auto-add the behavior to your model:
```php
// inside initialize() of controller
$this->loadComponent('Captcha.Captcha');
```
The component has the advantage that it also auto-loads the Captcha helper for your template forms.
Otherwise you need to manually load it.

Restrict automatic behavior attachment to selected actions:
```php
$this->loadComponent('Captcha.Captcha', [
    'actions' => ['add', 'edit'],
]);
```

Now load the helper, e.g in your AppView:
```php
$this->loadHelper('Captcha.Captcha');
```

Add a captcha control in your form:
```php
echo $this->Captcha->render(['placeholder' => __('Please solve the riddle')]);
```

That's it, it should now display the images to solve.
The helper stores the active challenge UUID in a hidden `captcha_uuid` field and uses that UUID in the image URL instead of exposing the internal numeric database id.

`render()` by default adds both active and passive form elements.
Use `control()` for the active result input and hidden UUID only. Attach `Captcha.PassiveCaptcha` separately to validate the passive field rendered by `render()`. Rendering a field does not attach its behavior. A correct or wrong answer consumes the active token; render a fresh form after a rejected submission.
The passive element is only a low-assurance honeypot and should be treated as supplemental filtering, not as the main protection layer.

## Working with model-less forms
E.g. for a contact form, first add this in your controller's `initialize()`:
```php
$this->loadComponent('Captcha.Captcha', ['auto' => false]);
```

Then inside your action, use `addValidation()` to inject the plugin's validation rules into the form validator:
```php
use App\Form\ContactForm; // Your Cake\Form\Form subclass

$contactForm = new ContactForm();

if ($this->request->is('post')) {
    $this->Captcha->addValidation($contactForm->getValidator());

    if ($contactForm->execute($this->request->getData())) {
        // Send email and redirect
    }
    // Display validation errors
}
```

Also here, don't forget to add the helper call.

## Engine configuration

Set shared engine options under the `Captcha` key in `config/app.php`. The helper, behavior, and public image preparation must use the same engine. The component accepts only `auto` and `actions`; it does not forward engine options.

For JPEG images:

```php
use Captcha\Engine\MathEngine;

return [
    'Captcha' => [
        'imageFormat' => MathEngine::FORMAT_JPEG,
        'ext' => 'jpg',
    ],
];
```

The default is PNG without a URL extension. Configure authentication middleware to allow visitors to reach the public image action.

## Invisible stored tokens

To retain database, session, timing, and single-use checks without a visible riddle:

```php
use Captcha\Engine\NullEngine;

return [
    'Captcha' => [
        'engine' => NullEngine::class,
        'minTime' => 6,
    ],
];
```

Attach `Captcha.Captcha` and use `render()` in the template. Attach `Captcha.PassiveCaptcha` separately to validate its honeypot. This mode provides basic nuisance filtering without a challenge requiring a human answer.

## Custom engines

Set `Captcha.mathType` to a class implementing `Captcha\Engine\Math\MathInterface` to change the math expression. The constructor receives the engine configuration; `getExpression(): string` returns the expression and `getValue(): string` its answer. MathEngine renders the expression with the bundled math publisher.

For a different challenge, set `Captcha.engine` to a class implementing `Captcha\Engine\EngineInterface`. Its constructor receives the configuration. `generate(): array` supplies the stored `result` and binary `image`; `buildValidator(Validator $validator): void` adds validation rules. The behavior registers itself as the `captcha` validation provider before calling the engine. Rules using `validateCaptchaResult` must specify `'provider' => 'captcha'`; table behavior method forwarding is removed in CakePHP 6. Keep the generated values and template controls consistent with those rules.

## Throttle and Garbage Collect
With such a DB driven tool you must clean out your table daily or even hourly based on the traffic.
Use the `maxTime` config to define the time span a captcha is valid, and when it can be removed as outdated.
The built in solution here is to auto-clean when creating images on a probability level (0...100). Default for `cleanupProbability` is `10` (percent).
`cleanupProbability => 0` disables automatic cleanup. See [upgrade notes](../guide/upgrading#unreleased-fixes) if using a release before this fix. For scheduled cleanup, call `fetchTable('Captcha.Captchas')->cleanup()` from your own command, or use the admin cleanup action. The plugin has no cleanup CLI command.

The `minTime` is by default 2 seconds and make sure you cannot auto-post a form too fast.

One should also include a throttle limit, so you cannot fill up the DB.
The built in mechanism is a `maxPerUser` value (defaults to 100) which prevents entering more than this amount per ip or session.
If a form gets built too often, new captcha rows will stop being persisted temporarily once this limit is reached.
The default issuance window is 60 minutes, controlled by `deadlockMinutes`. Used tokens are excluded. The count matches either IP or session; visitors sharing an address may share the issuance limit.
Note that this feature is primarily about limiting DB growth from issued challenges.

Separately, active captchas also include a verification-side rate limiter by default:

```php
'Captcha' => [
    'verifyRateLimit' => [
        'enabled' => true,
        'maxFailures' => 5,
        'window' => 600,
        'scope' => 'ip_session', // 'ip_session' or 'ip'
        'cache' => 'default',
    ],
],
```

This limiter applies to failed verification attempts, not challenge creation. It is enabled by default and is tracked via Cake Cache. The default policy counts wrong answers as well as invalid or already-used `captcha_uuid` submissions. Timing failures (`minTime`/`maxTime`) do not consume that budget.

The same verification policy applies to `NullEngine`. After the timing checks pass, its token
is consumed atomically and cannot be reused. `solved` remains `null`, since there was no riddle.
A successful verification clears previous failures, provided any attached honeypot checks pass too.

## Verification cache

Use a shared cache with atomic `add` and `increment` operations, such as Redis or Memcached,
for enforcement across concurrent requests and application workers. Set its duration to at least
`verifyRateLimit.window`. The default file-cache fallback uses a read/write counter and can lose
increments under concurrent load. Array cache is for tests; Null cache cannot enforce a limit.

The limiter uses fixed time buckets, so the budget resets at the next bucket boundary rather
than a full window after the latest failure. `ip_session` separates sessions at the same IP;
`ip` shares their budget and may also group unrelated users behind a shared address.

## Larger captcha images

Check the binary column capacity for your database and image sizes.
For MySQL the type `blob` is used when detected as DB.
Use an application migration if larger images require a different column definition.
