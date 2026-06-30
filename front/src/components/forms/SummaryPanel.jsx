export function SummaryPanel({ type = "income", amount = "14 $", compact = false }) {
  const isExpense = type === "expense";
  return (
    <aside className={`form-card rounded-2xl bg-app-card-soft p-6 ${compact ? "xl:max-w-[349px]" : ""}`}>
      <h2 className="mb-6 text-start text-xl font-medium text-white">ملخص القيد</h2>
      <div className={`rounded-2xl border ${isExpense ? "border-app-red bg-app-red/20" : "border-app-green bg-app-green/20"} p-5 text-center`}>
        <p className="text-base text-app-muted-light">{isExpense ? "إجمالي المصروف" : "إجمالي الإيراد"}</p>
        <p className={`mt-3 text-2xl font-medium ${isExpense ? "text-app-red" : "text-app-green"}`}>{amount}</p>
      </div>
      <dl className="mt-7 space-y-5 text-sm">
        <div className="flex items-center justify-between"><dt className="text-app-muted-light">نوع المعاملة</dt><dd><span className={`${isExpense ? "border-app-red bg-app-red/30 text-app-red" : "border-app-green bg-app-green/30 text-app-green"} rounded-lg border px-5 py-1.5`}>{isExpense ? "مصروف" : "إيراد"}</span></dd></div>
        <div className="flex items-center justify-between"><dt className="text-app-muted-light">رقم المرجع</dt><dd className="text-white">98865654</dd></div>
        <div className="flex items-center justify-between"><dt className="text-app-muted-light">التاريخ</dt><dd className="text-white">2025/54/65</dd></div>
        <div className="flex items-center justify-between"><dt className="text-app-muted-light">المستخدم</dt><dd className="text-white">محمد الاسعد</dd></div>
        <div className="flex items-center justify-between"><dt className="text-app-muted-light">الحالة</dt><dd><span className="rounded-lg border border-app-yellow bg-app-yellow/30 px-4 py-1.5 text-app-yellow">قيد المراجعة</span></dd></div>
      </dl>
      <div className="my-8 border-t border-white/10" />
      <ul className="space-y-3 text-start text-sm text-app-muted-light">
        {[
          "سيتم إنشاء قيد محاسبي مزدوج تلقائيا",
          "تسجل العملية في سجل التدقيق فور الحفظ",
          "تأكد من صحة المبلغ قبل الاعتماد",
        ].map((item) => (
          <li key={item} className="flex items-center gap-4"><span className="size-4 rounded-full bg-app-yellow" /><span>{item}</span></li>
        ))}
      </ul>
    </aside>
  );
}
