/**
 * Explains the read-only schedule data source.
 */
export default function ScheduleTips() {
  return (
    <div className="space-y-1 rounded-xl border border-app-line bg-app-card-soft px-5 py-4 text-xs leading-relaxed text-app-muted">
      <p className="text-sm font-medium text-app-text">مصدر بيانات الجدول</p>
      <p>تُجلب بيانات الحصص والمدربين تلقائياً من النظام بحسب الفرع المحدد.</p>
      <p>الجدول مخصص للعرض والطباعة فقط، ولا يدعم الإدخال أو التعديل المحلي.</p>
    </div>
  );
}
