"use client";

import DataTable from "@/components/ui/DataTable";
import Button from "@/components/ui/Button";
import { EyeIcon } from "@/components/icons/Icons";
import { formatDate, formatMoney } from "@/lib/utils";

function StatusBadge({ status, label }) {
  const isFrozen = status === "frozen";
  return (
    <span
      className={`inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium ${
        isFrozen
          ? "border border-cyan-500/30 bg-cyan-950/20 text-cyan-300"
          : "border border-app-red/30 bg-app-red/10 text-app-red"
      }`}
    >
      <span>{isFrozen ? "❄️" : "❌"}</span>
      <span>{label}</span>
    </span>
  );
}

export default function FrozenTerminatedReportTable({
  rows,
  summary,
  isLoading,
  onSelectRecord,
}) {
  const currency = summary.currency_type === "SYP" ? "ل.س" : summary.currency_type;

  const columns = [
    {
      key: "memberNumber",
      label: "رقم العضوية",
      width: "90px",
    },
    {
      key: "memberName",
      label: "اللاعب",
      width: "minmax(110px, 1.2fr)",
    },
    {
      key: "memberPhone",
      label: "رقم الهاتف",
      width: "minmax(100px, 0.9fr)",
      render: (value) => (
        <span className="font-mono text-xs" dir="ltr">
          {value}
        </span>
      ),
    },
    {
      key: "planName",
      label: "خطة الاشتراك",
      width: "minmax(110px, 1.2fr)",
    },
    {
      key: "statusLabel",
      label: "الحالة",
      width: "minmax(95px, 0.8fr)",
      sortable: false,
      render: (value, row) => (
        <StatusBadge status={row.status} label={value} />
      ),
    },
    {
      key: "eventDate",
      label: "تاريخ الحدث",
      width: "minmax(95px, 0.8fr)",
      render: (value) => formatDate(value),
    },
    {
      key: "reason",
      label: "السبب",
      width: "minmax(100px, 1.3fr)",
      render: (value) => (
        <span className="truncate block text-xs text-app-muted-light" title={value}>
          {value}
        </span>
      ),
    },
    {
      key: "totalAmount",
      label: "القيمة",
      width: "minmax(85px, 0.8fr)",
      type: "money",
      render: (value) => (
        <span className="font-medium text-app-yellow">
          {formatMoney(value, currency)}
        </span>
      ),
    },
    {
      key: "actions",
      label: "الإجراءات",
      width: "95px",
      sortable: false,
      render: (_, row) => (
        <Button
          type="button"
          tone="outline"
          size="sm"
          icon={<EyeIcon className="size-3.5" />}
          onClick={() => onSelectRecord(row)}
          className="h-8 px-2.5 text-xs"
        >
          التفاصيل
        </Button>
      ),
    },
  ];

  return (
    <DataTable
      title="سجلات الاشتراكات المجمدة والملغاة"
      subtitle={`${rows.length.toLocaleString("ar")} سجل مطابق للفلاتر الحالية`}
      columns={columns}
      rows={rows}
      getRowKey={(row) => row.id}
      showAdd={false}
      showSearch={false}
      showFilter={false}
      showExport={false}
      isLoading={isLoading}
      pageSize={10}
      pageSizeOptions={[10, 20, 50, 100]}
      emptyMessage="لا توجد اشتراكات مجمدة أو ملغاة مطابقة للفلاتر المختارة."
    />
  );
}
