# php-ulid

[![codecov](https://codecov.io/gh/yu-ichiro/php-ulid/branch/main/graph/badge.svg?token=IP6W0OKEVW)](https://codecov.io/gh/yu-ichiro/php-ulid)
![CI Status](https://github.com/yu-ichiro/php-ulid/actions/workflows/ci.yml/badge.svg)
[![Latest Stable Version](http://poser.pugx.org/yu-ichiro/ulid/v)](https://packagist.org/packages/yu-ichiro/ulid) 
[![Total Downloads](http://poser.pugx.org/yu-ichiro/ulid/downloads)](https://packagist.org/packages/yu-ichiro/ulid)
[![Latest Unstable Version](http://poser.pugx.org/yu-ichiro/ulid/v/unstable)](https://packagist.org/packages/yu-ichiro/ulid)
[![License](http://poser.pugx.org/yu-ichiro/ulid/license)](https://packagist.org/packages/yu-ichiro/ulid)
[![PHP Version Require](http://poser.pugx.org/yu-ichiro/ulid/require/php)](https://packagist.org/packages/yu-ichiro/ulid)

A simple and robust implementation of [ULID](https://github.com/ulid/spec) in PHP with no dependency.

Works on its own, but utilizes Ramsey\Uuid\Uuid if present. 

# Usage

```php

use Ulid\Ulid

new Ulid();  // ULID {01FQ75VPEBGY1JZSRD03EMM5QM}
new Ulid('01FQ75VPEBGY1JZSRD03EMM5QM');  // ULID {01FQ75VPEBGY1JZSRD03EMM5QM}
new Ulid('017dce5d-d9cb-8783-2fe7-0d00dd4a16f4');  // ULID {01FQ75VPEBGY1JZSRD03EMM5QM}

(string) new Ulid(); // "01FQ75VPEBGY1JZSRD03EMM5QM"
(new Ulid())->jsonSerialize(); // "01FQ75VPEBGY1JZSRD03EMM5QM"
(new Ulid())->toUuid(); // "017dce5d-d9cb-8783-2fe7-0d00dd4a16f4"
```