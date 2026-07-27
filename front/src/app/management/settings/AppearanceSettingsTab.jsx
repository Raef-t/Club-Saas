import Dropdown from "@/components/ui/Dropdown";
import { MoonIcon, SunIcon, TagIcon } from "@/components/icons/Icons";

const TIME_FORMAT_OPTIONS = [
  { value: "12", label: "نظام 12 ساعة (ص/م)" },
  { value: "24", label: "نظام 24 ساعة" },
];

/**
 * Controls visual theme and time-display preferences.
 */
export default function AppearanceSettingsTab({
  theme,
  onThemeChange,
  timeFormat,
  onTimeFormatChange,
}) {
  return (
    <section className="space-y-6">
      <div>
        <h3 className="text-right text-lg font-medium text-app-text">مظهر النظام والواجهة</h3>
        <p className="mt-1 text-right text-sm text-app-muted-light">
          تخصيص الواجهة واختيار سمة الألوان ونظام عرض الوقت. تُحفظ هذه الخيارات تلقائياً.
        </p>
      </div>

      <div className="space-y-4">
        <div>
          <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
            <TagIcon className="size-4 shrink-0 text-app-yellow" />
            <span>السمة والمظهر العام</span>
          </span>
          <div className="grid grid-cols-2 gap-4">
            <ThemeButton
              active={theme === "light"}
              icon={SunIcon}
              label="الوضع المضيء"
              onClick={() => onThemeChange("light")}
            />
            <ThemeButton
              active={theme === "dark"}
              icon={MoonIcon}
              label="الوضع الداكن"
              onClick={() => onThemeChange("dark")}
            />
          </div>
        </div>

        <label className="block text-right">
          <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
            <TagIcon className="size-4 shrink-0 text-app-yellow" />
            <span>نظام عرض التوقيت</span>
          </span>
          <Dropdown
            options={TIME_FORMAT_OPTIONS}
            value={timeFormat}
            onChange={onTimeFormatChange}
            placeholder="اختر نظام عرض الوقت"
            buttonClassName="h-[46px]"
          />
        </label>
      </div>
    </section>
  );
}

/**
 * Renders one selectable visual theme.
 */
function ThemeButton({ active, icon: Icon, label, onClick }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={`flex flex-col items-center justify-center gap-3 rounded-xl border p-4 transition ${
        active
          ? "border-app-yellow bg-app-yellow-soft/10 text-app-yellow"
          : "border-app-line bg-app-panel-soft text-app-muted hover:text-app-text"
      }`}
    >
      <Icon className="size-6" />
      <span className="text-sm font-medium">{label}</span>
    </button>
  );
}
