# Captcha Plugin Documentation

This plugin aims to ship with robust and most importantly "user-friendly" captchas.
There is nothing more annoying as captcha images you can't make out the content for 5+ trials.

It is also not supposed to replace the Security/Csrf components and bot-protection mechanisms.
More likely one would use them side by side.

Simple math captchas are also usually a bit more fun than trying to figure out some unreadable words behind colorful bars.
But since this plugin ships with a highly extensible interface solution, you can write and use your own captcha image solution.

## Active vs Passive

This plugin ships with two different types of captchas:

- [Active](/docs/Active.md): User input required
- [Passive](/docs/Passive.md): Honeypot trap and additional bot protection

They can also be combined for maximum captcha effectiveness.
