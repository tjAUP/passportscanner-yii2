# PassportScanner for Yii2

This package provides the Yii2 widget and asset bundle for
[`tjaup/passportscanner`](https://github.com/tjAUP/passportscanner). The core
package contains the browser scanner and its local OCR assets; this package
registers those assets and renders the scanner in a Yii2 form. Scans are
processed locally. Neither package saves forms, uploads passport images, or
sends images to an OCR service.

Source: [tjAUP/passportscanner-yii2](https://github.com/tjAUP/passportscanner-yii2)

## Install in a Yii2 application

Register both GitHub repositories with Composer, then require only the Yii2
package. Composer installs the core package as its dependency:

```sh
composer config repositories.passportscanner vcs https://github.com/tjAUP/passportscanner
composer config repositories.passportscanner-yii2 vcs https://github.com/tjAUP/passportscanner-yii2
composer require "tjaup/passportscanner-yii2:*"
```

Both repository entries are needed because Composer does not inherit
repository settings from dependencies. `*` lets Composer select the latest
compatible release available when you run `composer require` or
`composer update tjaup/passportscanner-yii2 -W`. `composer install` uses the
versions recorded in `composer.lock`; commit that file for reproducible
deployments. Review major upgrades before deploying them.

The PHP widget class names stay the same.

## Add the scanner to a Yii2 form

Use the widget in a Yii2 view:

```php
<?php
use TjAup\PassportScanner;

echo $form->field($model, 'passport')
    ->widget(PassportScanner::className(), [])
    ->label(false);
```

This renders a text input bound to `$model->passport` and scanner controls.
The widget registers the browser script and publishes all scanner assets,
including the local OCR worker, core, and language data. No asset path is
needed in the view. PHP registers the assets and markup; JavaScript performs
the scan in the browser and calls `PassportScanner.init(config)`.

### Complete Yii2 form example

Define the corresponding attributes on your Yii2 model and render the inputs
with IDs that match the widget's field selectors:

```php
<?php
use TjAup\PassportScanner;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$form = ActiveForm::begin([
    'options' => ['enctype' => 'multipart/form-data'],
]);

echo $form->field($model, 'name')->textInput(['id' => 'traveler-name']);
echo $form->field($model, 'firstName')->textInput(['id' => 'traveler-first-name']);
echo $form->field($model, 'lastName')->textInput(['id' => 'traveler-last-name']);
echo $form->field($model, 'title')->dropDownList(
    ['MR' => 'Mr', 'MS' => 'Ms'],
    ['prompt' => 'Select title', 'id' => 'traveler-title']
);
echo $form->field($model, 'gender')->dropDownList(
    ['male' => 'Male', 'female' => 'Female'],
    ['prompt' => 'Select gender', 'id' => 'traveler-gender']
);
echo $form->field($model, 'dob')->input('date', ['id' => 'traveler-dob']);
echo $form->field($model, 'expiry')->input('date', ['id' => 'traveler-expiry']);
echo $form->field($model, 'nationality')->dropDownList(
    [
        'Malaysia' => 'Malaysia',
        'Singapore' => 'Singapore',
        'United States' => 'United States',
    ],
    ['prompt' => 'Select nationality', 'id' => 'traveler-nationality']
);
echo $form->field($model, 'nationalityCode')->textInput(['id' => 'traveler-nationality-code']);
echo $form->field($model, 'documentCode')->textInput(['id' => 'traveler-document-code']);
echo $form->field($model, 'issuingCountryCode')->textInput(['id' => 'traveler-issuing-country-code']);
echo $form->field($model, 'sex')->textInput(['id' => 'traveler-sex']);
echo $form->field($model, 'optionalData')->textInput(['id' => 'traveler-optional-data']);
echo $form->field($model, 'passportImg')->fileInput(['id' => 'traveler-passport-image']);

echo $form->field($model, 'passportNo')->widget(PassportScanner::class, [
    'options' => ['id' => 'traveler-passport'],
    'fields' => [
        'name' => '#traveler-name',
        'firstName' => '#traveler-first-name',
        'lastName' => '#traveler-last-name',
        'title' => '#traveler-title',
        'gender' => '#traveler-gender',
        'dob' => '#traveler-dob',
        'expiry' => '#traveler-expiry',
        'nationality' => '#traveler-nationality',
        'nationalityCode' => '#traveler-nationality-code',
        'documentCode' => '#traveler-document-code',
        'issuingCountryCode' => '#traveler-issuing-country-code',
        'sex' => '#traveler-sex',
        'optionalData' => '#traveler-optional-data',
        'passportImg' => '#traveler-passport-image',
    ],
    'scannerOptions' => [
        'dateFormat' => 'Y-m-d',
        'countryNames' => [
            'MY' => 'Malaysia',
            'SG' => 'Singapore',
            'US' => 'United States',
        ],
    ],
])->label(false);

echo Html::tag('output', '', ['id' => 'traveler-scan-review', 'aria-live' => 'polite']);
echo Html::submitButton('Save after checking the passport');
ActiveForm::end();

$this->registerJs(<<<'JS'
document.addEventListener('passportscanner:result', function (event) {
    if (event.target.id !== 'traveler-passport') return;
    const { fields, checks, verifiedCount, complete } = event.detail;
    document.getElementById('traveler-scan-review').textContent =
        `Verified ${verifiedCount}/3 checked fields. ` +
        (checks.passport && fields.passportNo ? 'Passport number read. ' : 'Check the passport number. ') +
        (complete ? 'Review all values before saving.' : 'Fill missing values manually.');
});
JS
);
```

The widget always maps the verified `passport` value to its own text input,
even when the Yii2 model attribute is named `passportNo`. If your model uses
`passport`, use that attribute in the widget call instead. The scan event
provides the same number as `fields.passport` and `fields.passportNo`. The
other selectors must point to fields in the rendered form. `passportImg` must
point to a file input if you want the selected or captured image attached to
the form.
`scannerOptions` supports `dateFormat` and `countryNames`. The widget supplies
the scanner container, passport input, and OCR asset URL automatically.
Rendering the widget always enables its scanner controls.

`dateFormat` uses [PHP `date_format()` tokens](https://www.php.net/manual/en/datetime.format.php)
for both `dob` and `expiry`. For example, `Y/m/d` gives `1974/08/12`,
`y/m/d` gives `74/08/12`, and `d m y` gives `12 08 74`. Separators and text can
be placed anywhere; prefix a format letter with `\` to print it literally.
The default is `Y-m-d`, which is required for native HTML `type="date"` fields.
MRZ dates have no time or timezone, so PHP time and timezone tokens represent
midnight UTC. The older `ymd` and `dmy` settings still produce their original
`Y-m-d` and `d/m/Y` outputs.

### Field keys

| Field key | Scanned value |
| --- | --- |
| `name` | Full name |
| `firstName` | Given name(s) |
| `lastName` | Surname |
| `title` | `MR` or `MS` |
| `gender` | `male` or `female` |
| `passport` | Passport number, when verified; automatically mapped to the widget input |
| `passportNo` | The same verified passport number, as an optional additional mapping |
| `dob` | Date of birth, when verified |
| `expiry` | Expiry date, when verified |
| `nationality` | Value from `countryNames` |
| `nationalityCode` | Three-letter nationality code from the MRZ |
| `documentCode` | Passport document code from the MRZ |
| `issuingCountryCode` | Three-letter issuing country or organization code |
| `sex` | MRZ sex marker, `M` or `F` when present |
| `optionalData` | Issuer-defined MRZ data, only when its check digit passes |
| `passportImg` | Selected or captured image file |

The scanner dispatches a bubbling `passportscanner:result` event on the
passport input after a usable scan. The event's `detail.fields` contains the
accepted MRZ values, including `detail.fields.passportNo`, and
`detail.checks` reports the passport, birth date, expiry date, optional data,
and composite check results. The optional data has no universal meaning;
some issuers use it for a personal number. Your form code can listen for the
event if it needs to react to a scan. Each widget keeps its own scanner state,
so several travelers can be scanned on the same page. Opening a camera closes
any other scanner's camera; image uploads remain independent.

Check every populated value against the passport before saving. The widget
does not save the form or upload the image.

### Multiple or dynamic travelers

For repeated traveler rows, use a unique Yii2 model index for each traveler
and scope field selectors to that row. Inside an existing `ActiveForm`:

```php
<?php
use TjAup\PassportScanner;
use yii\helpers\Html;

foreach ($travelers as $index => $traveler) {
    echo Html::beginTag('div', ['class' => 'traveler-row']);
    echo $form->field($traveler, "[$index]name")
        ->textInput(['class' => 'traveler-name']);
    echo $form->field($traveler, "[$index]firstName")
        ->textInput(['class' => 'traveler-first-name']);
    echo $form->field($traveler, "[$index]lastName")
        ->textInput(['class' => 'traveler-last-name']);
    echo $form->field($traveler, "[$index]title")
        ->dropDownList(['MR' => 'Mr', 'MS' => 'Ms'], ['prompt' => 'Select title', 'class' => 'traveler-title']);
    echo $form->field($traveler, "[$index]gender")
        ->dropDownList(['male' => 'Male', 'female' => 'Female'], ['prompt' => 'Select gender', 'class' => 'traveler-gender']);
    echo $form->field($traveler, "[$index]dob")
        ->input('date', ['class' => 'traveler-dob']);
    echo $form->field($traveler, "[$index]expiry")
        ->input('date', ['class' => 'traveler-expiry']);
    echo $form->field($traveler, "[$index]nationality")
        ->dropDownList(
            ['Malaysia' => 'Malaysia', 'Singapore' => 'Singapore', 'United States' => 'United States'],
            ['prompt' => 'Select nationality', 'class' => 'traveler-nationality']
        );
    echo $form->field($traveler, "[$index]nationalityCode")
        ->textInput(['class' => 'traveler-nationality-code']);
    echo $form->field($traveler, "[$index]documentCode")
        ->textInput(['class' => 'traveler-document-code']);
    echo $form->field($traveler, "[$index]issuingCountryCode")
        ->textInput(['class' => 'traveler-issuing-country-code']);
    echo $form->field($traveler, "[$index]sex")
        ->textInput(['class' => 'traveler-sex']);
    echo $form->field($traveler, "[$index]optionalData")
        ->textInput(['class' => 'traveler-optional-data']);
    echo $form->field($traveler, "[$index]passportImg")
        ->fileInput(['class' => 'traveler-passport-image']);
    echo $form->field($traveler, "[$index]passportNo")
        ->widget(PassportScanner::class, [
            'fields' => [
                'name' => '.traveler-name',
                'firstName' => '.traveler-first-name',
                'lastName' => '.traveler-last-name',
                'title' => '.traveler-title',
                'gender' => '.traveler-gender',
                'dob' => '.traveler-dob',
                'expiry' => '.traveler-expiry',
                'nationality' => '.traveler-nationality',
                'nationalityCode' => '.traveler-nationality-code',
                'documentCode' => '.traveler-document-code',
                'issuingCountryCode' => '.traveler-issuing-country-code',
                'sex' => '.traveler-sex',
                'optionalData' => '.traveler-optional-data',
                'passportImg' => '.traveler-passport-image',
            ],
            'scannerOptions' => [
                'fieldRoot' => '.traveler-row',
                'dateFormat' => 'Y-m-d',
                'countryNames' => [
                    'MY' => 'Malaysia',
                    'SG' => 'Singapore',
                    'US' => 'United States',
                ],
            ],
        ]);
    echo Html::endTag('div');
}
```

`fieldRoot` resolves each field selector inside the nearest matching ancestor
of that scanner. Other [field keys](#field-keys) work the
same way. The browser script initializes widgets added to the page later and
cleans up scanners whose rows are removed. If the first widget will arrive
through AJAX, register the asset bundle in the parent view so the script is
already loaded:

```php
<?php
use TjAup\PassportScanner\Yii2\PassportScannerAsset;

PassportScannerAsset::register($this);
```

If a row is hidden but kept in the DOM, call
`PassportScanner.stop(passportInput)` when hiding it and
`PassportScanner.initFromElement(passportInput)` when showing it again.
`PassportScanner.stop()` without an argument stops all scanners. Keep Yii2
input IDs and model indexes unique when cloning rows.

## Local OCR assets and privacy

The widget publishes the core package's complete `dist/` directory, including
`dist/ocr/`. Tesseract.js 5.1.1, its worker and core, and English language data
are bundled locally. Keep the OCR file layout unchanged. Serve
`eng.traineddata.gz` as binary without an HTTP `Content-Encoding` header.
Camera use requires HTTPS or localhost and browser permission. A restrictive
Content Security Policy must allow same-origin scripts, workers, and
WebAssembly.

Third-party licenses and attribution are in the core package's
[THIRD_PARTY_NOTICES.md](https://github.com/tjAUP/passportscanner/blob/main/THIRD_PARTY_NOTICES.md)
and `dist/ocr/licenses/`.

## Acknowledgment

This project was developed with assistance from OpenAI Codex.
