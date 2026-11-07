<?php

namespace App\Services;

use App\Models\Setting;

class WelcomeScreenService
{
    public const KEY_ENABLED = 'welcome_enabled';
    public const KEY_TITLE = 'welcome_title';
    public const KEY_MESSAGE = 'welcome_message';
    public const KEY_FEATURES = 'welcome_features';
    public const KEY_BUTTON_TEXT = 'welcome_button_text';

    public static function defaults(): array
    {
        return [
            self::KEY_ENABLED => '1',
            self::KEY_TITLE => '🎉 مرحبًا بك في توصيلة برو',
            self::KEY_MESSAGE => "أنت الآن من أوائل مستخدمي توصيلة برو.\nنعمل حاليًا على تطوير التطبيق وإضافة المزيد من السائقين وأصحاب الأنشطة لتقديم أفضل تجربة ممكنة.",
            self::KEY_FEATURES => json_encode([
                'إنشاء حساب مجاني بالكامل.',
                'لا توجد أي رسوم أو عمولات خلال الفترة التجريبية.',
                'استخدام جميع خدمات التطبيق مجانًا.',
                'يمكنك إرسال اقتراحاتك وملاحظاتك للمساهمة في تطوير التطبيق.',
                'يتم إضافة خدمات ومزايا جديدة بشكل مستمر.',
            ], JSON_UNESCAPED_UNICODE),
            self::KEY_BUTTON_TEXT => 'ابدأ الآن',
        ];
    }

    public static function ensureDefaults(): void
    {
        foreach (self::defaults() as $key => $value) {
            if (Setting::getValue($key) === null) {
                Setting::setValue($key, $value);
            }
        }
    }

    public static function adminFormData(): array
    {
        self::ensureDefaults();

        $defaults = self::defaults();
        $featuresJson = Setting::getValue(self::KEY_FEATURES, $defaults[self::KEY_FEATURES]);
        $features = json_decode((string) $featuresJson, true);

        return [
            'enabled' => Setting::getValue(self::KEY_ENABLED, '1') === '1',
            'title' => Setting::getValue(self::KEY_TITLE, $defaults[self::KEY_TITLE]),
            'message' => Setting::getValue(self::KEY_MESSAGE, $defaults[self::KEY_MESSAGE]),
            'features_text' => is_array($features)
                ? implode("\n", $features)
                : (string) $featuresJson,
            'button_text' => Setting::getValue(self::KEY_BUTTON_TEXT, $defaults[self::KEY_BUTTON_TEXT]),
        ];
    }

    public static function apiPayload(): array
    {
        $data = self::adminFormData();
        $features = array_values(array_filter(array_map(
            static fn ($line) => trim((string) $line),
            preg_split('/\R/u', (string) $data['features_text']) ?: []
        )));

        return [
            'status_code' => 200,
            'enabled' => $data['enabled'],
            'title' => $data['title'],
            'message' => $data['message'],
            'features' => $features,
            'button_text' => $data['button_text'],
        ];
    }

    public static function updateFromRequest(array $validated): void
    {
        Setting::setValue(self::KEY_ENABLED, !empty($validated['enabled']) ? '1' : '0');
        Setting::setValue(self::KEY_TITLE, $validated['title']);
        Setting::setValue(self::KEY_MESSAGE, $validated['message']);

        $features = array_values(array_filter(array_map(
            static fn ($line) => trim((string) $line),
            preg_split('/\R/u', (string) $validated['features_text']) ?: []
        )));

        Setting::setValue(self::KEY_FEATURES, json_encode($features, JSON_UNESCAPED_UNICODE));
        Setting::setValue(self::KEY_BUTTON_TEXT, $validated['button_text']);
    }
}
