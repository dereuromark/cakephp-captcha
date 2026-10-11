# Getting started

After [installation](./installation), add a honeypot to an existing form that saves through a CakePHP table. This example uses a `Contacts` table; substitute your form's table and template.

## Add validation

In `src/Model/Table/ContactsTable.php`, attach the behavior inside the existing `initialize()` method:

```php
$this->addBehavior('Captcha.PassiveCaptcha');
```

Keep the normal `newEntity()` or `patchEntity()` and `save()` flow. The behavior adds validation for a hidden field named `email_homepage`. The field is not a database column.

## Render the field

Load the helper in `src/View/AppView.php` inside `initialize()`:

```php
$this->loadHelper('Captcha.Captcha');
```

In your existing template, place the honeypot between `Form->create()` and `Form->end()`:

```php
echo $this->Captcha->passive();
```

The submitted field must be present and contain an empty string. A missing or filled field causes validation to fail. Check the entity's errors before showing success, as with your other validation rules.

## Check the result

Submit the form normally and confirm it saves. Then edit the hidden field in your browser's developer tools and submit a value: saving should fail. Hiding the field alone provides no protection; the behavior must validate the submitted data.

For a strict content security policy, configure a [CSS class](../reference/passive#csp-compatible-rendering-passiveclass) instead of the default inline style. Show a visible validation error if you enable verification throttling, since its error belongs to the hidden field.

A honeypot catches bots that fill hidden fields. Add [a math challenge](../reference/active) when you need a stored token and single-use verification. For forms without a table, follow the [model-less form example](../reference/passive#working-with-model-less-forms).
