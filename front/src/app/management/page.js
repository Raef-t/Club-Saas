import StatCard from "@/components/ui/StatCard";
import SectionCard from "@/components/ui/SectionCard";
import LineChart from "@/components/charts/LineChart";
import { ArrowUpIcon } from "@/components/icons/Icons";

const dashboardStats = [
  {
    title: "اشتراكات تنتهي قريباً",
    value: "24",
    change: "+8%",
    helper: "عن أمس",
    tone: "blue",
  },
  {
    title: "حضور اليوم",
    value: "875",
    change: "+8%",
    helper: "عن أمس",
    tone: "green",
  },
  {
    title: "اشتراكات نشطة",
    value: "76747",
    change: "+8%",
    helper: "عن أمس",
    tone: "orange",
  },
  {
    title: "المدربون",
    value: "6536",
    change: "+8%",
    helper: "عن أمس",
    tone: "cyan",
  },
  {
    title: "إجمالي الأعضاء",
    value: "43315",
    change: "+8%",
    helper: "عن الشهر الماضي",
    tone: "yellow",
  },
];

const attendanceChart = {
  labels: ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"],
  yellow: [88, 78, 62, 82, 83, 84, 90, 48, 89, 66],
};

const subscriptionMix = [
  { label: "vip", value: 45, color: "#16e79b" },
  { label: "حصص", value: 30, color: "#f2f2f2" },
  { label: "شهري", value: 25, color: "#fccd03" },
];

const todaySessions = [
  {
    title: "حصة كمال أجسام صباحية",
    coach: "كابتن عمر",
    time: "07:09",
    status: "جارية",
    tone: "green",
  },
  {
    title: "حصة يوغا",
    coach: "كابتن عمر",
    time: "10:00",
    status: "قادمة",
    tone: "yellow",
  },
  {
    title: "كروس فيت",
    coach: "كابتن عمر",
    time: "08:45",
    status: "جارية",
    tone: "green",
  },
];

const recentMembers = [
  { name: "أحمد الزهراوي", sport: "كمال أجسام", color: "bg-[#d9b08c]" },
  { name: "أحمد الزهراوي", sport: "كمال أجسام", color: "bg-[#0e9bd8]" },
  { name: "أحمد الزهراوي", sport: "كمال أجسام", color: "bg-[#b26a36]" },
];

function SubscriptionDonut({ items }) {
  const total = items.reduce((sum, item) => sum + item.value, 0);
  let offset = 25;

  return (
    <div className="flex items-center justify-center gap-8 px-5 pb-5 pt-1">
      <ul className="space-y-3 text-sm text-app-text">
        {items.map((item) => (
          <li key={item.label} className="flex items-center justify-end gap-3">
            <span>{item.label}</span>
            <span
              className="size-2.5 rounded-full"
              style={{ backgroundColor: item.color }}
            />
          </li>
        ))}
      </ul>
      <svg
        className="size-36 -rotate-90"
        viewBox="0 0 120 120"
        aria-hidden="true"
      >
        <circle
          cx="60"
          cy="60"
          r="38"
          fill="none"
          stroke="#222"
          strokeWidth="18"
        />
        {items.map((item) => {
          const length = (item.value / total) * 239;
          const segment = (
            <circle
              key={item.label}
              cx="60"
              cy="60"
              r="38"
              fill="none"
              stroke={item.color}
              strokeWidth="18"
              strokeDasharray={`${length} ${239 - length}`}
              strokeDashoffset={-offset}
            />
          );
          offset += length;
          return segment;
        })}
      </svg>
    </div>
  );
}

function SessionItem({ item }) {
  const isGreen = item.tone === "green";

  return (
    <div className="flex items-center justify-between gap-4">
      <span
        className={`rounded-lg px-3 py-1 text-xs ${
          isGreen
            ? "bg-app-green/20 text-[#00dc1a]"
            : "bg-app-yellow/20 text-app-yellow"
        }`}
      >
        {item.status}
      </span>
      <div className="flex min-w-0 items-center gap-4">
        <div className="min-w-0 text-end">
          <h3 className="truncate text-[15px] font-medium text-app-text">
            {item.title}
          </h3>
          <p className="mt-1 truncate text-xs text-app-muted">{item.coach}</p>
        </div>
        <span className="rounded-lg bg-app-yellow/20 px-3 py-2 text-xs text-app-yellow">
          {item.time}
        </span>
      </div>
    </div>
  );
}

function MemberRow({ item }) {
  return (
    <div className="flex h-12 items-center justify-between rounded-lg bg-app-card-soft px-3">
      <span className="rounded-lg bg-app-green/20 px-3 py-1 text-xs text-[#00e500]">
        نشط
      </span>
      <div className="flex min-w-0 items-center gap-3">
        <div className="min-w-0 text-end">
          <h3 className="truncate text-sm font-medium text-app-text">
            {item.name}
          </h3>
          <p className="mt-1 truncate text-xs text-app-muted">{item.sport}</p>
        </div>
        <div
          className={`grid size-8 place-items-center rounded-full text-xs text-white ${item.color}`}
        >
          {item.name.charAt(0)}
        </div>
      </div>
    </div>
  );
}

function IncomeMovement() {
  return (
    <div className="flex h-12 items-center gap-3 rounded-lg bg-app-card-soft px-3">
      <div className="grid size-9 place-items-center rounded bg-app-green/20 text-app-green">
        <ArrowUpIcon className="size-5 rotate-[225deg]" />
      </div>
      <div className="min-w-0 flex-1 text-end">
        <h3 className="truncate text-sm font-medium text-app-text">
          محمد الاسعد
        </h3>
        <p className="truncate text-xs text-app-muted">اشتراك vip سنوي</p>
      </div>
      <div className="text-center text-xs text-app-muted">
        <strong className="block text-base font-medium text-app-green">
          123$
        </strong>
        منذ 5 د
      </div>
    </div>
  );
}

export default function ManagementPage() {
  return (
    <div className="space-y-5" dir="rtl">
      <section className="grid grid-cols-[repeat(auto-fit,minmax(min(100%,172px),1fr))] gap-3 sm:gap-5">
        {dashboardStats.map((stat) => (
          <StatCard key={stat.title} {...stat} />
        ))}
      </section>

      <section className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <SectionCard
          title="إحصائيات الحضور"
          action="آخر 7 أيام"
          className="min-h-[208px]"
        >
          <LineChart data={attendanceChart} />
        </SectionCard>

        <SectionCard title="توزيع الاشتراكات" className="min-h-[206px]">
          <SubscriptionDonut items={subscriptionMix} />
        </SectionCard>
      </section>

      <section className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <SectionCard
          title="آخر الأعضاء المسجلين"
          subtitle="أحدث الحركات الاشتراكية"
          action="عرض الكل"
          className="min-h-[239px]"
          contentClassName="space-y-4 px-6 pb-5 pt-2"
        >
          {recentMembers.map((item, index) => (
            <MemberRow key={`${item.name}-${index}`} item={item} />
          ))}
          <IncomeMovement />
        </SectionCard>
        <SectionCard
          title="حصص اليوم"
          action="عرض الكل"
          className="min-h-[239px]"
          contentClassName="space-y-5 px-5 pb-5 pt-2"
        >
          {todaySessions.map((item) => (
            <SessionItem key={`${item.title}-${item.time}`} item={item} />
          ))}
        </SectionCard>
      </section>
    </div>
  );
}
