"use client";

export default function PlayerNavbar({ activeTab, onTabChange }) {
  const tabs = [
    {
      id: "profile",
      label: "بطاقتي",
      icon: (
        <svg className="size-4 sm:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7">
          <rect x="3" y="4" width="18" height="16" rx="3" />
          <path d="M7 8h10M7 12h6M7 16h3" />
        </svg>
      ),
    },
    {
      id: "attendance",
      label: "الحضور",
      icon: (
        <svg className="size-4 sm:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7">
          <circle cx="12" cy="12" r="9" />
          <path d="M12 7v5l3 3" />
        </svg>
      ),
    },
    {
      id: "payments",
      label: "المدفوعات",
      icon: (
        <svg className="size-4 sm:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7">
          <rect x="2" y="5" width="20" height="14" rx="2" />
          <line x1="2" y1="10" x2="22" y2="10" />
          <circle cx="6.5" cy="14.5" r="1" fill="currentColor" />
        </svg>
      ),
    },
    {
      id: "plans",
      label: "الفعاليات",
      icon: (
        <svg className="size-4 sm:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7">
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
        </svg>
      ),
    },
    {
      id: "coaches",
      label: "المدربين",
      icon: (
        <svg className="size-4 sm:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
      ),
    },
  ];

  return (
    <nav className="mb-6 overflow-x-auto no-scrollbar" aria-label="أقسام حساب اللاعب">
      <div className="flex items-center gap-1.5 rounded-2xl border border-app-line/60 bg-app-card-soft/80 p-1.5 backdrop-blur-md min-w-max sm:min-w-0 sm:justify-around">
        {tabs.map((tab) => {
          const isActive = activeTab === tab.id;
          return (
            <button
              key={tab.id}
              type="button"
              onClick={() => onTabChange(tab.id)}
              className={`flex flex-1 items-center justify-center gap-2 rounded-xl px-3 py-2 sm:px-4 sm:py-2.5 text-xs sm:text-sm font-medium transition-all ${
                isActive
                  ? "bg-app-yellow text-black font-semibold shadow-md shadow-app-yellow/20"
                  : "text-app-muted-light hover:bg-white/5 hover:text-white"
              }`}
            >
              <span className={isActive ? "text-black" : "text-app-yellow"}>{tab.icon}</span>
              <span>{tab.label}</span>
            </button>
          );
        })}
      </div>
    </nav>
  );
}
