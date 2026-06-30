export const appPalette = {
  bg: "#07080C",
  panel: "#0D0E13",
  panelSoft: "#090C11",
  card: "#161717",
  cardSoft: "#222222",
  border: "#282828",
  borderSoft: "#1B1B1B",
  yellow: "#FCCD03",
  text: "#EFEFEF",
  muted: "#A1A1A1",
  mutedLight: "#C1C1C1",
  green: "#13AC49",
  green2: "#00A806",
  red: "#E40000",
  blue: "#0755FF",
  purple: "#7925FF",
};

export const systemCards = [
  {
    title: "نظام المحاسبة",
    href: "/accounting",
    description:
      "لوحة مالية كاملة للإيرادات، المصاريف، الرواتب، الصندوق، والديون.",
    badge: "جاهز",
    stats: ["12 صفحة", "Mock Data", "RTL"],
  },
  {
    title: "نظام التقارير",
    href: "/reports",
    description: "تقارير أداء وإحصائيات مقارنة للنادي والفروع والمدربين.",
    badge: "جاهز مبدئياً",
    stats: ["Charts", "KPIs", "Filters"],
  },
  {
    title: "إدارة النادي",
    href: "#",
    description: "الأعضاء، الفروع، الحضور، الأجهزة، والاشتراكات التشغيلية.",
    badge: "قيد التطوير",
    disabled: true,
    stats: ["Members", "Branches", "Attendance"],
  },
];

export const accountingMenu = [
  { title: "لوحة التحكم", href: "/accounting" },
  {
    title: "إدارة الإيرادات",
    href: "/accounting/revenues",
    children: [
      { title: "إيرادات الاشتراكات", href: "/accounting/revenues" },
      { title: "إيرادات إضافية", href: "/accounting/revenues/additional" },
      { title: "العروض الترويجية", href: "/accounting/revenues/offers" },
    ],
  },
  { title: "إدارة المصاريف", href: "/accounting/expenses" },
  { title: "الصندوق اليومي", href: "/accounting/cashbox" },
  { title: "اشتراكات الأعضاء", href: "/accounting/subscriptions" },
  {
    title: "الرواتب",
    href: "/accounting/salaries/trainers",
    children: [
      { title: "رواتب المدربين", href: "/accounting/salaries/trainers" },
      { title: "رواتب الموظفين", href: "/accounting/salaries/employees" },
    ],
  },
  { title: "الذمم الدائنة", href: "/accounting/debts" },
  { title: "نظام التقارير", href: "/reports" },
];

export const pageMeta = {
  "/accounting": {
    title: "النظرة العامة للأمور المالية",
    subtitle: "ملخص شامل للأداء المالي يومياً وشهرياً",
  },
  "/accounting/revenues": {
    title: "إدارة الإيرادات",
    subtitle: "جميع مصادر الدخل: اشتراكات، مبيعات، وتأجير",
  },
  "/accounting/revenues/additional": {
    title: "الإيرادات الإضافية",
    subtitle: "جميع الإيرادات المرتبطة بالخدمات الخارجية",
  },
  "/accounting/revenues/offers": {
    title: "العروض الترويجية",
    subtitle: "عروض الخصومات والباقات الموسمية",
  },
  "/accounting/expenses": {
    title: "إدارة المصاريف",
    subtitle: "مصادر ثابتة ومتغيرة، مع سداد كامل",
  },
  "/accounting/cashbox": {
    title: "الصندوق اليومي",
    subtitle: "إدارة الحركة النقدية اليومية والمطابقة",
  },
  "/accounting/subscriptions": {
    title: "اشتراكات الأعضاء",
    subtitle: "سجل المدفوعات، الفواتير، وإدارة الأقساط",
  },
  "/accounting/salaries/trainers": {
    title: "رواتب المدربين",
    subtitle: "احتساب آلي وفق نظام النسب أو نسبة الحصص",
  },
  "/accounting/salaries/employees": {
    title: "رواتب الموظفين",
    subtitle: "حضور، إضافي، مكافآت مع المحاسبة",
  },
  "/accounting/debts": {
    title: "الذمم الدائنة",
    subtitle: "المبالغ المستحقة على النادي",
  },

  "/accounting/cashbox/create": {
    title: "الصندوق اليومي",
    subtitle: "الصندوق اليومي > إضافة قيد",
  },
  "/accounting/cashbox/entry": {
    title: "الصندوق اليومي",
    subtitle: "الصندوق اليومي > إضافة قيد",
  },
  "/accounting/revenues/create": {
    title: "إضافة إيراد جديد",
    subtitle: "تسجيل وارد مالي جديد - إيراد تشغيلي أو غير تشغيلي",
  },
  "/accounting/expenses/create": {
    title: "إضافة مصروف جديد",
    subtitle: "تسجيل بند مصروف وتوثيق بيانات التحويل أو الدفع",
  },
  "/accounting/subscriptions/payments/create": {
    title: "اشتراكات الأعضاء",
    subtitle: "اشتراكات الأعضاء > إضافة دفعة",
  },
  "/accounting/salaries/trainers/create": {
    title: "رواتب المدربين",
    subtitle: "رواتب المدربين > إضافة كشف",
  },
  "/reports": {
    title: "نظام التقارير",
    subtitle: "ملخص تقارير الأداء المالي والإداري للنادي",
  },
};

export const overviewStats = [
  {
    title: "إيرادات اليوم",
    value: "24",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
  {
    title: "مصاريف اليوم",
    value: "24",
    change: "+8%",
    helper: "عن أمس",
    tone: "green",
  },
  {
    title: "صافي الربح",
    value: "24",
    change: "+8%",
    helper: "عن أمس",
    tone: "purple",
    negative: true,
  },
  {
    title: "رصيد الصندوق",
    value: "24",
    change: "+8%",
    helper: "عن أمس",
    tone: "blue",
  },
  {
    title: "ديون مستحقة",
    value: "24",
    change: "+8%",
    helper: "عن أمس",
    tone: "blue",
  },
];

export const revenueStats = [
  {
    title: "إيرادات الاشتراكات",
    value: "23456$",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
  {
    title: "إيرادات إضافية",
    value: "34566$",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
  {
    title: "عروض ترويجية",
    value: "456$",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
  {
    title: "تأجير الصالة",
    value: "45767$",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
];

export const expenseStats = [
  {
    title: "إيرادات الاشتراكات",
    value: "23456$",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
  {
    title: "إيرادات إضافية ثابتة",
    value: "34566$",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
  {
    title: "عروض ترويجية",
    value: "456$",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
  {
    title: "تأجير الصالة",
    value: "45767$",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
];

export const cashboxStats = [
  {
    title: "الرصيد الافتتاحي",
    value: "23456$",
    helper: "08:30 صباحاً",
    tone: "yellow",
  },
  {
    title: "إجمالي المقبوضات",
    value: "34566$",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
  {
    title: "إجمالي المقبوضات",
    value: "456$",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
  {
    title: "الرصيد الحالي",
    value: "45767$",
    helper: "آخر تحديث 08:45",
    tone: "yellow",
  },
];

export const subscriptionStats = [
  {
    title: "إجمالي المحصل",
    value: "34566$",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
  { title: "المتبقي", value: "34566$", helper: "14 عضو", tone: "yellow" },
  {
    title: "اشتراكات",
    value: "456$",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
    negative: true,
  },
  { title: "المتأخر", value: "34566$", helper: "2 ملفات", tone: "yellow" },
];

export const trainersSalaryStats = [
  {
    title: "إجمالي الرواتب",
    value: "34566$",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
  { title: "مدربون راتب ثابت", value: "14", tone: "yellow" },
  { title: "مدربون بالنسبة", value: "34", tone: "yellow" },
  { title: "إجمالي المشتركين", value: "43", tone: "yellow" },
];

export const employeesSalaryStats = [
  {
    title: "إجمالي رواتب الموظفين",
    value: "34566$",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
  { title: "عدد الموظفين", value: "14", tone: "yellow" },
  { title: "ساعات إضافي", value: "34", tone: "yellow" },
  {
    title: "مكافآت",
    value: "34",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
];

export const debtStats = [
  { title: "إجمالي الذمم", value: "14", tone: "yellow" },
  { title: "مستحقات هذا الأسبوع", value: "14", tone: "yellow" },
  { title: "عدد الموردين", value: "22", tone: "yellow" },
  { title: "متأخر السداد", value: "76554 SP", tone: "yellow" },
];

export const reportStats = [
  { title: "إجمالي الإيرادات", value: "14", tone: "yellow" },
  {
    title: "متوسط فترة التحصيل",
    value: "22 يوم",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
  {
    title: "نسبة التحصيل",
    value: "87%",
    change: "+8%",
    helper: "عن أمس",
    tone: "yellow",
  },
];

export const branchStats = [
  {
    title: "نادي الرجال",
    value: "",
    helper: "الفرع الرئيسي - الموكامبو",
    tone: "yellow",
    compact: true,
  },
  {
    title: "نادي النساء",
    value: "",
    helper: "الفرع الرئيسي - الفرقان",
    tone: "yellow",
    compact: true,
  },
];

export const monthlyProfit = [
  { label: "Jan", value: 34 },
  { label: "Feb", value: 12 },
  { label: "Mar", value: 41 },
  { label: "Apr", value: 5 },
  { label: "May", value: 111 },
  { label: "Jun", value: 72 },
  { label: "July", value: 51 },
  { label: "Oct", value: 102 },
  { label: "Sep", value: 89 },
  { label: "Sep", value: 41 },
];

export const comparisonChart = {
  labels: ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"],
  yellow: [80, 68, 42, 70, 72, 73, 78, 30, 78, 45],
  green: [48, 38, 20, 40, 41, 24, 47, 12, 47, 23],
};

export const branchComparison = {
  labels: ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"],
  yellow: [78, 65, 40, 66, 68, 70, 76, 28, 76, 43],
  green: [46, 36, 18, 38, 39, 22, 45, 11, 46, 21],
};

export const upcomingPayments = [
  { title: "إيجار المقر", date: "1 ديسمبر", amount: "187$" },
  { title: "إيجار المقر", date: "1 ديسمبر", amount: "187$" },
  { title: "إيجار المقر", date: "1 ديسمبر", amount: "187$" },
];

export const recentTransactions = [
  {
    title: "فاتورة كهرباء",
    description: "مصاريف ثابتة",
    amount: "123$",
    time: "منذ 5 د",
    type: "out",
  },
  {
    title: "محمد الأسعد",
    description: "اشتراك VIP سنوي",
    amount: "123$",
    time: "منذ 5 د",
    type: "in",
  },
  {
    title: "صيانة أجهزة",
    description: "مصاريف متغيرة",
    amount: "123$",
    time: "منذ 5 د",
    type: "out",
  },
  {
    title: "محمد الأسعد",
    description: "اشتراك VIP سنوي",
    amount: "123$",
    time: "منذ 5 د",
    type: "in",
  },
];

export const salarySummary = [
  { label: "المدربون", value: "25" },
  { label: "الإجمالي", value: "4543$", tone: "yellow" },
  { label: "تم الصرف", value: "23/12" },
];

export const revenueColumns = [
  { key: "id", label: "الرقم", width: "0.55fr" },
  { key: "member", label: "العضو" },
  { key: "date", label: "التاريخ" },
  { key: "method", label: "طريقة الدفع" },
  { key: "amount", label: "المبلغ", type: "money" },
  { key: "type", label: "نوع الاشتراك" },
  { key: "status", label: "الحالة", type: "status" },
];

export const revenues = [
  {
    id: "#1",
    member: "محمد الأسعد",
    date: "12/34/2026",
    method: "كاش",
    amount: "123$",
    type: "سنوي VIP",
    status: "مدفوع",
  },
  {
    id: "#2",
    member: "أحمد شحادة",
    date: "12/34/2026",
    method: "كاش",
    amount: "123$",
    type: "شهري",
    status: "معلق",
  },
  {
    id: "#3",
    member: "أحمد الأسعد",
    date: "12/34/2026",
    method: "كاش",
    amount: "123$",
    type: "حصصي",
    status: "متأخر",
  },
];

export const additionalRevenueColumns = [
  { key: "id", label: "الرقم", width: "0.55fr" },
  { key: "status", label: "الحالة", type: "status" },
  { key: "date", label: "التاريخ" },
  { key: "method", label: "طريقة الدفع" },
  { key: "amount", label: "المبلغ", type: "money" },
  { key: "source", label: "المصدر" },
];

export const additionalRevenues = [
  {
    id: "#1",
    status: "مدفوع",
    date: "12/34/2026",
    method: "كاش",
    amount: "123$",
    source: "مبيعات المكملات",
  },
  {
    id: "#2",
    status: "معلق",
    date: "12/34/2026",
    method: "كاش",
    amount: "123$",
    source: "البوفيه",
  },
  {
    id: "#3",
    status: "متأخر",
    date: "12/34/2026",
    method: "كاش",
    amount: "123$",
    source: "تأجير الصالة",
  },
];

export const expenseColumns = [
  { key: "id", label: "الرقم", width: "0.55fr" },
  { key: "item", label: "البند" },
  { key: "category", label: "التصنيف" },
  { key: "resource", label: "المورد" },
  { key: "method", label: "طريقة الدفع" },
  { key: "amount", label: "المبلغ", type: "money" },
  { key: "date", label: "التاريخ" },
  { key: "status", label: "الحالة", type: "status" },
];

export const expenses = [
  {
    id: "#1",
    item: "فاتورة كهرباء",
    category: "ثابت/كهرباء",
    resource: "شركة الكهرباء",
    method: "كاش",
    amount: "123$",
    date: "23/23/2026",
    status: "مدفوع",
  },
  {
    id: "#2",
    item: "أحمد الأسعد",
    category: "تشغيلي",
    resource: "شهري",
    method: "كاش",
    amount: "123$",
    date: "12/34/2026",
    status: "معلق",
  },
  {
    id: "#3",
    item: "أحمد الأسعد",
    category: "حصصي",
    resource: "حصصي",
    method: "كاش",
    amount: "123$",
    date: "12/34/2026",
    status: "متأخر",
  },
  {
    id: "#4",
    item: "أحمد الأسعد",
    category: "حصصي",
    resource: "حصصي",
    method: "كاش",
    amount: "123$",
    date: "12/34/2026",
    status: "متأخر",
  },
];

export const cashboxColumns = [
  { key: "id", label: "الرقم", width: "0.55fr" },
  { key: "time", label: "الوقت" },
  { key: "type", label: "نوع الحركة" },
  { key: "income", label: "دخل", type: "in" },
  { key: "outcome", label: "خرج", type: "out" },
  { key: "balance", label: "الرصيد", type: "money" },
  { key: "by", label: "بواسطة" },
];

export const cashboxRows = [
  {
    id: "#1",
    time: "08:00 صباحاً",
    type: "افتتاح الصندوق",
    income: "+ 5,000",
    outcome: "-----",
    balance: "5,000 sp",
    by: "محمد الأسعد",
  },
  {
    id: "#2",
    time: "01:87 مساءً",
    type: "اشتراك نقدي",
    income: "+ 650",
    outcome: "-----",
    balance: "5,650 sp",
    by: "محمد الأسعد",
  },
  {
    id: "#3",
    time: "08:00 صباحاً",
    type: "مبيعات بوفيه",
    income: "+ 320",
    outcome: "-----",
    balance: "5,970 sp",
    by: "محمد الأسعد",
  },
  {
    id: "#4",
    time: "08:00 صباحاً",
    type: "مبيعات مكملات",
    income: "-----",
    outcome: "180-",
    balance: "5,790 SP",
    by: "محمد الأسعد",
  },
];

export const subscriptionColumns = [
  { key: "id", label: "الرقم", width: "0.55fr" },
  { key: "member", label: "العضو" },
  { key: "plan", label: "الاشتراكات" },
  { key: "total", label: "الإجمالي", type: "money" },
  { key: "paid", label: "المدفوع", type: "in" },
  { key: "remaining", label: "المتبقي", type: "warning" },
  { key: "due", label: "الاستحقاق التالي" },
  { key: "status", label: "الحالة", type: "status" },
];

export const subscriptions = [
  {
    id: "#1",
    member: "محمد الأسعد",
    plan: "سنوي VIP",
    total: "2236 SP",
    paid: "5,000",
    remaining: "180",
    due: "12/3/2025",
    status: "مدفوع",
  },
  {
    id: "#2",
    member: "محمد الأسعد",
    plan: "سنوي",
    total: "2236 SP",
    paid: "650",
    remaining: "180",
    due: "12/3/2025",
    status: "معلق",
  },
  {
    id: "#3",
    member: "محمد الأسعد",
    plan: "شهري",
    total: "2236 SP",
    paid: "320",
    remaining: "180",
    due: "12/3/2025",
    status: "متأخر",
  },
  {
    id: "#4",
    member: "محمد الأسعد",
    plan: "حصصي",
    total: "2236 SP",
    paid: "320",
    remaining: "180",
    due: "12/3/2025",
    status: "متأخر",
  },
];

export const trainersSalaryColumns = [
  { key: "id", label: "الرقم", width: "0.55fr" },
  { key: "trainer", label: "المدرب" },
  { key: "salaryType", label: "نظام الراتب" },
  { key: "base", label: "الأساسي" },
  { key: "sessions", label: "حصص" },
  { key: "percent", label: "نسبة المدرب" },
  { key: "net", label: "صافي الراتب", type: "money" },
  { key: "status", label: "الحالة", type: "status" },
];

export const trainersSalaries = [
  {
    id: "#1",
    trainer: "محمد الأسعد",
    salaryType: "ثابت + صالة",
    base: "2236 SP",
    sessions: "-----",
    percent: "-----",
    net: "5,000 sp",
    status: "مدفوع",
  },
  {
    id: "#2",
    trainer: "محمد الأسعد",
    salaryType: "نسبة - يوغا",
    base: "2236 SP",
    sessions: "60%",
    percent: "60%",
    net: "5,000 sp",
    status: "معلق",
  },
  {
    id: "#3",
    trainer: "محمد الأسعد",
    salaryType: "ثابت - كروس فت",
    base: "2236 SP",
    sessions: "-----",
    percent: "-----",
    net: "5,000 sp",
    status: "قيد المراجعة",
  },
  {
    id: "#4",
    trainer: "محمد الأسعد",
    salaryType: "نسبة - رقص",
    base: "2236 SP",
    sessions: "55%",
    percent: "55%",
    net: "5,000 sp",
    status: "متأخر",
  },
];

export const employeesSalaryColumns = [
  { key: "id", label: "الرقم", width: "0.55fr" },
  { key: "employee", label: "الموظف" },
  { key: "job", label: "الوظيفة" },
  { key: "attendance", label: "أيام الحضور" },
  { key: "overtime", label: "ساعات إضافي" },
  { key: "bonus", label: "مكافأة", type: "in" },
  { key: "discount", label: "خصم", type: "out" },
  { key: "net", label: "الصافي", type: "money" },
  { key: "status", label: "الحالة", type: "status" },
];

export const employeesSalaries = [
  {
    id: "#1",
    employee: "محمد الأسعد",
    job: "استقبال",
    attendance: "23",
    overtime: "3",
    bonus: "650+",
    discount: "500-",
    net: "5,000 sp",
    status: "مدفوع",
  },
  {
    id: "#2",
    employee: "محمد الأسعد",
    job: "إدارة",
    attendance: "23",
    overtime: "5",
    bonus: "650+",
    discount: "500-",
    net: "5,000 sp",
    status: "معلق",
  },
  {
    id: "#3",
    employee: "محمد الأسعد",
    job: "نظافة",
    attendance: "43",
    overtime: "6",
    bonus: "650+",
    discount: "---",
    net: "5,000 sp",
    status: "قيد المراجعة",
  },
  {
    id: "#4",
    employee: "محمد الأسعد",
    job: "أمن",
    attendance: "54",
    overtime: "2",
    bonus: "650+",
    discount: "---",
    net: "5,000 sp",
    status: "متأخر",
  },
];

export const debtColumns = [
  { key: "id", label: "الرقم", width: "0.55fr" },
  { key: "party", label: "الجهة/العضو" },
  { key: "amount", label: "المبلغ", type: "money" },
  { key: "age", label: "عمر الدين" },
  { key: "contact", label: "للتواصل" },
  { key: "status", label: "الحالة", type: "status" },
];

export const debts = [
  {
    id: "#1",
    party: "شركة الفجر / إعلانات",
    amount: "5,000 sp",
    age: "24 يوم",
    contact: "0987654422",
    status: "مدفوع",
  },
  {
    id: "#2",
    party: "ريم الحربي / عضوية",
    amount: "5,000 sp",
    age: "24 يوم",
    contact: "0987654422",
    status: "معلق",
  },
  {
    id: "#3",
    party: "مدرسة الرواد - تأجير",
    amount: "5,000 sp",
    age: "24 يوم",
    contact: "0987654422",
    status: "قيد المراجعة",
  },
  {
    id: "#4",
    party: "بدر القرني - أقساط",
    amount: "5,000 sp",
    age: "24 يوم",
    contact: "0987654422",
    status: "متأخر",
  },
];

export const branchMiniCards = [
  { label: "إيرادات", value: "1665335" },
  { label: "مصاريف", value: "1665335" },
  { label: "صافي", value: "1665335" },
];
