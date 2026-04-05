<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Forms\BelongsToSelectField;
use Vortex\Admin\Forms\CheckboxField;
use Vortex\Admin\Forms\DateField;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\HiddenField;
use Vortex\Admin\Forms\HtmlField;
use Vortex\Admin\Forms\MarkdownField;
use Vortex\Admin\Forms\NumberField;
use Vortex\Admin\Forms\PasswordField;
use Vortex\Admin\Forms\SelectField;
use Vortex\Admin\Forms\TagsField;
use Vortex\Admin\Forms\TextField;
use Vortex\Admin\Forms\ToggleField;
use Vortex\Admin\Forms\UploadField;
use Vortex\Database\Model;

final class FormFieldsTest extends TestCase
{
    public function testCheckboxNormalizesOnAndOff(): void
    {
        $f = CheckboxField::make('ok');
        self::assertTrue($f->normalizeRequestValue('1'));
        self::assertTrue($f->normalizeRequestValue('on'));
        self::assertFalse($f->normalizeRequestValue(null));
    }

    public function testNumberInteger(): void
    {
        $f = NumberField::make('n')->integer();
        self::assertSame(7, $f->normalizeRequestValue('7'));
        self::assertSame('', $f->normalizeRequestValue(''));
    }

    public function testNumberEmptyAsNull(): void
    {
        $f = NumberField::make('n')->emptyAsNull();
        self::assertNull($f->normalizeRequestValue(''));
    }

    public function testDateNormalizes(): void
    {
        $f = DateField::make('d');
        self::assertSame('2024-06-01', $f->normalizeRequestValue('2024-06-01'));
    }

    public function testSelectToViewArrayContainsOptions(): void
    {
        $f = SelectField::make('role', ['admin' => 'Admin', 'user' => 'User']);
        $v = $f->toViewArray();
        self::assertSame('select', $v['inputKind']);
        self::assertSame(['admin' => 'Admin', 'user' => 'User'], $v['options']);
    }

    public function testHiddenAndPasswordKinds(): void
    {
        self::assertSame('hidden', HiddenField::make('x')->inputKind());
        self::assertSame('password', PasswordField::make('p')->inputKind());
        self::assertSame('text', TextField::make('t')->inputKind());
    }

    public function testToggleNormalizes(): void
    {
        $f = ToggleField::make('active');
        self::assertTrue($f->normalizeRequestValue('1'));
        self::assertFalse($f->normalizeRequestValue(null));
    }

    public function testTagsNormalizesCsvAndJson(): void
    {
        $f = TagsField::make('tags');
        self::assertSame('a,b', $f->normalizeRequestValue('a, b'));
        $j = TagsField::make('t')->asJson();
        self::assertSame('["x","y"]', $j->normalizeRequestValue('["x","y"]'));
        self::assertSame('["a"]', $j->normalizeRequestValue('[{"value":"a"}]'));
    }

    public function testUploadFieldKeepsExisting(): void
    {
        $f = UploadField::make('file');
        self::assertSame('old/path.jpg', $f->normalizeUpload(null, 'old/path.jpg'));
    }

    public function testUploadFieldDiscardWhenEmpty(): void
    {
        $f = UploadField::make('file')->discardExistingWhenEmpty();
        self::assertSame('', $f->normalizeUpload(null, 'old/path.jpg'));
    }

    public function testFormMultipartAndRichFlags(): void
    {
        $plain = Form::make(TextField::make('t'));
        self::assertFalse($plain->requiresMultipart());
        self::assertSame(['markdown' => false, 'html' => false, 'tags' => false], $plain->richEditorAssets());

        $rich = Form::make(
            UploadField::make('a'),
            MarkdownField::make('m'),
            HtmlField::make('h'),
            TagsField::make('x'),
        );
        self::assertTrue($rich->requiresMultipart());
        $k = $rich->richEditorAssets();
        self::assertTrue($k['markdown']);
        self::assertTrue($k['html']);
        self::assertTrue($k['tags']);
    }

    public function testBelongsToSelectBuildsOptionsFromLoader(): void
    {
        $a = new SelectOptionModel();
        $a->id = 1;
        $a->title = 'One';
        $b = new SelectOptionModel();
        $b->id = 2;
        $b->title = 'Two';
        $f = BelongsToSelectField::make('category_id', SelectOptionModel::class, 'Category', 'title', 'id')
            ->withoutOrder()
            ->usingRelatedLoader(static fn (): array => [$a, $b]);
        $v = $f->toViewArray();
        self::assertSame('select', $v['inputKind']);
        self::assertSame(['1' => 'One', '2' => 'Two'], $v['options']);
    }

    public function testBelongsToSelectNullableNormalizesEmptyToNull(): void
    {
        $f = BelongsToSelectField::make('category_id', SelectOptionModel::class)
            ->nullable()
            ->withoutOrder()
            ->usingRelatedLoader(static fn (): array => []);
        self::assertNull($f->normalizeRequestValue(null));
        self::assertNull($f->normalizeRequestValue(''));
    }
}

final class SelectOptionModel extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['title'];
}
