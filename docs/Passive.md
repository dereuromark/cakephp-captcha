## Passive Captchas

By default, the plugin provides an unobtrusive passive captcha as "bot trap" aka "honeyput".

### Honeypot field
The `dummyField` config sets an input field (by default `email_homepage` control) as hidden field, and will fail if filled out.
This can only happen by a bot, which usually fills out all fields it finds.

If you want to only use this "passive captcha", then use the `Captcha.PassiveCaptcha` behavior instead.

```php
$this->MyTable->addBehavior('Captcha.PassiveCaptcha');
```

They can also be combined, though.


Now load the helper, e.g in your AppView:
```php
$this->loadHelper('Captcha.Captcha');
```

Add a passive captcha control in your form:
```php
echo $this->Captcha->passive();
```

That's it, now it should not validate the form if the honeypot was triggered.

### Working with model-less forms
E.g. for a contact form, first add this in your controller's `initialize()`:
```php
$this->loadComponent('Captcha.Captcha');
```

Then inside your action, use `addValidation()` to inject the plugin's validation rules into the form validator:
```php
use Tools\Form\ContactForm; // or any other form

$contactForm = new ContactForm();

if ($this->request->is('post')) {
    $this->Captcha->addValidation($contactForm->getValidator(), 'Passive');

    if ($contactForm->execute($this->request->getData())) {
        // Send email and redirect
    }
    // Display validation errors
}
```

Also here, don't forget to add the helper call.

### Configuration

You can configure it globally using Configure class - and `app.php`:
```
'Captcha' => [
    'dummyField' => ...,
    'log' => ...,
],
```

Use `'log' => true` if you want to log all honeypot events to type `info`.
