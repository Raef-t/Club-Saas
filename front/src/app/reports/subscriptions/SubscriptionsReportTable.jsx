"use client";

import DataTable from "@/components/ui/DataTable";
import { formatDate, formatMoney } from "@/lib/utils";

const statusClasses = {
  active: "status-success",
  finished: "status-danger",
  expired: "status-danger",
  frozen: "status-warning",
  terminated: "bg-app-muted/15 text-app-muted-light",
  cancelled: "bg-app-muted/15 text-app-muted-light",
  canceled: "bg-app-muted/15 text-app-muted-light",
};

const paymentStatusClasses = {
  paid: "status-success",
  fully_paid: "status-success",
  partially_paid: "status-warning",
  partial: "status-warning",
  unpaid: "status-danger",
};

function StatusBadge({ value, status, classes }) {
  return (
    <span
      className={`inline-flex rounded-md px-2.5 py-1 text-xs font-medium ${classes[status] || "bg-app-card-hover text-app-muted-light"}`}
    >
      {value}
    </span>
  );
}

export default function SubscriptionsReportTable({ rows, summary, isLoading }) {
  const currency = summary.currency_type === "SYP" ? "ل.س" : summary.currency_type;
  const columns = [
    { key: "membershipNumber", label: "رقم العضوية", width: "110px" },
    { key: "memberName", label: "اللاعب", width: "minmax(160px,1.35fr)" },
    { key: "phone", label: "رقم الهاتف", width: "130px" },
    { key: "planName", label: "خطة الاشتراك", width: "minmax(160px,1.2fr)" },
    { key: "coachName", label: "الكوتش", width: "minmax(140px,1fr)" },
    {
      key: "startDate",
      label: "تاريخ البداية",
      width: "125px",
      render: (value) => formatDate(value),
    },
    {
      key: "endDate",
      label: "تاريخ النهاية",
      width: "125px",
      render: (value) => formatDate(value),
    },
    {
      key: "totalAmount",
      label: "القيمة",
      width: "125px",
      type: "money",
      render: (value) => (
        <span className="font-medium text-app-yellow">{formatMoney(value, currency)}</span>
      ),
    },
    {
      key: "paidAmount",
      label: "المدفوع",
      width: "125px",
      render: (value) => (
        <span className="font-medium text-app-green">{formatMoney(value, currency)}</span>
      ),
    },
    {
      key: "remainingAmount",
      label: "المتبقي",
      width: "125px",
      render: (value) => (
        <span className="font-medium text-app-red">{formatMoney(value, currency)}</span>
      ),
    },
    {
      key: "statusLabel",
      label: "حالة الاشتراك",
      width: "125px",
      sortable: false,
      render: (value, row) => (
        <StatusBadge value={value} status={row.status} classes={statusClasses} />
      ),
    },
    {
      key: "paymentStatusLabel",
      label: "حالة الدفع",
      width: "135px",
      sortable: false,
      render: (value, row) => (
        <StatusBadge value={value} status={row.paymentStatus} classes={paymentStatusClasses} />
      ),
    },
  ];

  return (
    <DataTable
      title="سجلات الاشتراكات"
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
      minWidth="1740px"
      desktopScrollable
      emptyMessage="لا توجد اشتراكات مطابقة للفلاتر المختارة."
    />
  );
}
