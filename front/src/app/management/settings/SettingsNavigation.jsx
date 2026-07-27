import { CalendarIcon, ClockIcon, MoonIcon, SettingsIcon, SunIcon } from "@/components/icons/Icons";

const ITEMS = [
  { id: "general", label: "الإعدادات العامة", Icon: SettingsIcon },
  { id: "shifts", label: "ورديات الفروع", Icon: ClockIcon },
  { id: "holidays", label: "إجازات الفروع", Icon: CalendarIcon },
];

/**
 * Renders the settings section navigation without owning page state.
 */
export default function SettingsNavigation({ activeTab, onChange, theme }) {
  const AppearanceIcon = theme === "dark" ? MoonIcon : SunIcon;
  const items = [
    ITEMS[0],
    {
      id: "appearance",
      label: "المظهر والنظام",
      Icon: AppearanceIcon,
    },
    ...ITEMS.slice(1),
  ];

  return (
    <nav
      className="flex h-fit flex-col gap-2 rounded-2xl border border-app-line bg-app-panel p-3"
      aria-label="أقسام الإعدادات"
    >
      {items.map(({ id, label, Icon }) => (
        <button
          key={id}
          type="button"
          onClick={() => onChange(id)}
          className={`flex h-11 items-center gap-3 rounded-lg px-4 text-right text-sm font-medium transition ${
            activeTab === id
              ? "bg-app-yellow-soft text-app-yellow"
              : "text-app-muted-light hover:bg-app-line-soft hover:text-app-text"
          }`}
        >
          <Icon className="size-5 shrink-0" />
          <span>{label}</span>
        </button>
      ))}
    </nav>
  );
}
