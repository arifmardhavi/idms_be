<?php

namespace Tests\Unit;

use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Guard regression untuk pola "append tanpa accessor".
 *
 * Laravel memanggil accessor get{Studly}Attribute() secara tanpa cek di
 * HasAttributes::mutateAttribute(), jadi setiap atribut yang ada di $appends
 * WAJIB punya accessor. Kalau tidak, model akan melempar BadMethodCallException
 * saat diserialisasi (toArray/toJson/response()->json($model)).
 */
class ModelAppendsAccessorTest extends TestCase
{
    /**
     * @return array<int, array{0:string, 1:string}>
     */
    public static function appendsProvider(): array
    {
        $cases = [];

        foreach (glob(dirname(__DIR__, 2) . '/app/Models/*.php') as $file) {
            $class = 'App\\Models\\' . basename($file, '.php');

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract() || ! $reflection->hasProperty('appends')) {
                continue;
            }

            $property = $reflection->getProperty('appends');
            $property->setAccessible(true);

            foreach ((array) $property->getDefaultValue() as $key) {
                $cases[] = [$class, $key];
            }
        }

        return $cases;
    }

    /**
     * @dataProvider appendsProvider
     */
    public function test_appended_attribute_has_accessor(string $class, string $key): void
    {
        $method = 'get' . Str::studly($key) . 'Attribute';

        $this->assertTrue(
            method_exists($class, $method),
            "{$class} mendaftarkan '\$appends' => '{$key}' tapi tidak punya accessor {$method}()"
        );
    }

    public function test_provider_is_not_empty(): void
    {
        $this->assertNotEmpty(self::appendsProvider(), 'Tidak ada model yang diperiksa.');
    }
}
