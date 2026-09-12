"use client";

import Modal from "@/components/ui/Modal";
import DetailItem from "@/components/ui/DetailItem";
import { formatDate, formatMoney } from "@/lib/utils";
import { CheckCircleIcon, TagIcon, UsersIcon } from "@/components/icons/Icons";

export default function RenewalDetailsModal({ record, onClose }) {
  if (!record) return null;

  const currency = record.currencyType === "SYP" ? "ل.س" : record.currencyType;
  const isRenewed = record.statusType === "renewed";
  const renewal = record.renewalInfo;

  return (
    <Modal
      open={Boolean(record)}
      onClose={onClose}
      title={`تفاصيل اشتراك اللاعب: ${record.memberName}`}
      subtitle={`رقم العضوية: ${record.memberNumber} • الفرع: ${record.branchName}`}
      className="max-w-3xl"
    >
      <div className="space-y-6 text-right">
        {/* Renewal Status Banner */}
        {isRenewed ? (
          <div className="flex items-center gap-3 rounded-xl border border-app-green/30 bg-app-green/10 p-4 text-app-green">
            <CheckCircleIcon className="size-6 shrink-0 text-app-green" />
            <div className="min-w-0">
              <h4 className="text-sm font-semibold">تم تجديد هذا الاشتراك بنجاح</h4>
              <p className="mt-0.5 text-xs text-app-green/80">
                قام اللاعب بتجديد اشتراكه وتم تسجيل الاشتراك الجديد في النظام.
              </p>
            </div>
          </div>
        ) : (
          <div className="flex items-center gap-3 rounded-xl border border-app-red/30 bg-app-red/10 p-4 text-app-red">
            <TagIcon className="size-6 shrink-0 text-app-red" />
            <div className="min-w-0">
              <h4 className="text-sm font-semibold">الاشتراك منتهي ولم يتم تجديده</h4>
              <p className="mt-0.5 text-xs text-app-red/80">
                مضى على الانتهاء {record.daysSinceExpiration.toLocaleString("ar")} يوم. قيمة الفرصة الضائعة:{" "}
                {formatMoney(record.totalAmount, currency)}.
              </p>
            </div>
          </div>
        )}

        {/* Member & Contacts Section */}
        <div className="space-y-3">
          <div className="flex items-center gap-2 border-b border-app-line pb-2">
            <UsersIcon className="size-4 text-app-yellow" />
            <h3 className="text-sm font-semibold text-app-text">بيانات المشترك والتواصل</h3>
          </div>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <DetailItem label="اسم اللاعب" value={record.memberName} />
            <DetailItem label="رقم العضوية" value={record.memberNumber} />
            <DetailItem
              label="رقم الهاتف الأساسي"
              value={
                record.memberPhone !== "-" ? (
                  <a
                    href={`tel:${record.memberPhone}`}
                    className="text-app-yellow underline hover:text-app-yellow-hover"
                    dir="ltr"
                  >
                    {record.memberPhone}
                  </a>
                ) : (
                  "-"
                )
              }
            />
          </div>

          {record.contactPersons && record.contactPersons.length > 0 && (
            <div className="mt-3 rounded-xl border border-app-line bg-app-card-soft/40 p-3">
              <span className="block text-xs font-medium text-app-muted-light mb-2">
                جهات الاتصال المسجلة ({record.contactPersons.length.toLocaleString("ar")}):
              </span>
              <div className="grid gap-2 sm:grid-cols-2">
                {record.contactPersons.map((contact, i) => (
                  <div
                    key={contact.id || i}
                    className="flex items-center justify-between rounded-lg border border-app-line bg-app-panel px-3 py-2 text-xs"
                  >
                    <span className="text-app-muted-light">
                      {contact.name || "جهة اتصال"} ({contact.relation || "شخصي"}):
                    </span>
                    <a
                      href={`tel:${contact.phone_number}`}
                      className="font-medium text-app-yellow underline hover:text-app-yellow-hover"
                      dir="ltr"
                    >
                      {contact.phone_number}
                    </a>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Attendance & Absence Section */}
        <div className="space-y-3">
          <div className="flex items-center gap-2 border-b border-app-line pb-2">
            <TagIcon className="size-4 text-app-yellow" />
            <h3 className="text-sm font-semibold text-app-text">سجل الحضور والغياب</h3>
          </div>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <DetailItem label="فترة الغياب / الانقطاع" value={record.absenceFormatted} tone="yellow" />
            <DetailItem
              label="تاريخ آخر حضور"
              value={
                record.lastAttendanceDate
                  ? formatDate(record.lastAttendanceDate)
                  : "لم يحضر أبدًا"
              }
            />
            <DetailItem
              label="أيام الانتهاء"
              value={`${record.daysSinceExpiration.toLocaleString("ar")} يوم`}
            />
          </div>
        </div>

        {/* Subscription Info */}
        <div className="space-y-3">
          <div className="flex items-center gap-2 border-b border-app-line pb-2">
            <TagIcon className="size-4 text-app-yellow" />
            <h3 className="text-sm font-semibold text-app-text">تفاصيل الاشتراك المنتهي</h3>
          </div>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <DetailItem label="خطة الاشتراك" value={record.planName} />
            <DetailItem label="المدرب المسند" value={record.coachesNames} />
            <DetailItem
              label="الأنشطة"
              value={
                record.activities && record.activities.length > 0
                  ? record.activities.join("، ")
                  : "لا توجد أنشطة"
              }
            />
            <DetailItem label="تاريخ البداية" value={formatDate(record.startDate)} />
            <DetailItem label="تاريخ النهاية" value={formatDate(record.endDate)} />
            <DetailItem
              label="حالة الاشتراك"
              value={record.subscriptionStatus === "active" ? "ساري" : "منتهي"}
            />
            <DetailItem
              label="قيمة الاشتراك"
              value={formatMoney(record.totalAmount, currency)}
              tone="yellow"
            />
            <DetailItem
              label="المدفوع"
              value={formatMoney(record.paidAmount, currency)}
              tone="green"
            />
            <DetailItem
              label="المتبقي"
              value={formatMoney(record.remainingAmount, currency)}
              tone={record.remainingAmount > 0 ? "red" : "default"}
            />
          </div>
        </div>

        {/* New Renewal Details (if available) */}
        {isRenewed && renewal && (
          <div className="space-y-3 rounded-xl border border-app-green/40 bg-app-green/5 p-4">
            <div className="flex items-center gap-2 pb-2">
              <CheckCircleIcon className="size-4 text-app-green" />
              <h3 className="text-sm font-semibold text-app-green">بيانات التجديد الجديد</h3>
            </div>
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
              <DetailItem
                label="رقم الاشتراك الجديد"
                value={renewal.renewed_subscription_id ? `#${renewal.renewed_subscription_id}` : "-"}
              />
              <DetailItem label="الخطة الجديدة" value={renewal.new_plan_name || "-"} tone="green" />
              <DetailItem
                label="تاريخ التجديد"
                value={renewal.renewal_date ? formatDate(renewal.renewal_date) : "-"}
              />
              <DetailItem
                label="تاريخ النهاية الجديد"
                value={renewal.new_end_date ? formatDate(renewal.new_end_date) : "-"}
              />
              <DetailItem
                label="قيمة التجديد"
                value={
                  renewal.new_total_amount
                    ? formatMoney(
                        renewal.new_total_amount,
                        renewal.new_currency_type === "SYP" ? "ل.س" : renewal.new_currency_type
                      )
                    : "-"
                }
                tone="green"
              />
            </div>
          </div>
        )}
      </div>
    </Modal>
  );
}
