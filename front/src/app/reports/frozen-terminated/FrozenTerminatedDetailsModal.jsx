"use client";

import Modal from "@/components/ui/Modal";
import DetailItem from "@/components/ui/DetailItem";
import { formatDate, formatMoney } from "@/lib/utils";
import { SnowflakeIcon, TagIcon, UsersIcon } from "@/components/icons/Icons";

export default function FrozenTerminatedDetailsModal({ record, onClose }) {
  if (!record) return null;

  const currency = record.currencyType === "SYP" ? "ل.س" : record.currencyType;
  const isFrozen = record.status === "frozen";

  return (
    <Modal
      open={Boolean(record)}
      onClose={onClose}
      title={`تفاصيل اشتراك اللاعب: ${record.memberName}`}
      subtitle={`رقم العضوية: ${record.memberNumber} • الفرع: ${record.branchName}`}
      className="max-w-3xl"
    >
      <div className="space-y-6 text-right">
        {/* Status Highlight Banner */}
        {isFrozen ? (
          <div className="flex items-center gap-3 rounded-xl border border-cyan-500/30 bg-cyan-950/20 p-4 text-cyan-300">
            <SnowflakeIcon className="size-6 shrink-0 text-cyan-400" />
            <div className="min-w-0">
              <h4 className="text-sm font-semibold">اشتراك مجمّد مؤقتاً (Frozen)</h4>
              <p className="mt-0.5 text-xs text-cyan-200/80">
                {record.frozenDays > 0
                  ? `مدة التجميد: ${record.frozenDays.toLocaleString("ar")} يوم.`
                  : "تم إيقاف صلاحية الدخول مؤقتاً حتى إلغاء التجميد."}
              </p>
            </div>
          </div>
        ) : (
          <div className="flex items-center gap-3 rounded-xl border border-app-red/30 bg-app-red/10 p-4 text-app-red">
            <TagIcon className="size-6 shrink-0 text-app-red" />
            <div className="min-w-0">
              <h4 className="text-sm font-semibold">اشتراك ملغى نهائياً (Terminated)</h4>
              <p className="mt-0.5 text-xs text-app-red/80">
                تم إنهاء الاشتراك وخروجه من الخدمة.
              </p>
            </div>
          </div>
        )}

        {/* Reason Card */}
        <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-4">
          <span className="block text-xs font-semibold text-app-yellow mb-1.5">
            {isFrozen ? "سبب التجميد المسجل:" : "سبب الإلغاء المسجل:"}
          </span>
          <p className="text-sm text-app-text whitespace-pre-wrap leading-relaxed">
            {record.reason || "لم يُذكر سبب محدد"}
          </p>
        </div>

        {/* Member & Contacts */}
        <div className="space-y-3">
          <div className="flex items-center gap-2 border-b border-app-line pb-2">
            <UsersIcon className="size-4 text-app-yellow" />
            <h3 className="text-sm font-semibold text-app-text">بيانات المشترك والتواصل</h3>
          </div>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <DetailItem label="اسم اللاعب" value={record.memberName} />
            <DetailItem label="رقم العضوية" value={record.memberNumber} />
            <DetailItem
              label="الهاتف"
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
            <div className="mt-2 rounded-xl border border-app-line bg-app-card-soft/40 p-3">
              <span className="block text-xs font-medium text-app-muted-light mb-2">
                جهات الاتصال ({record.contactPersons.length.toLocaleString("ar")}):
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

        {/* Event Dates & Plan Details */}
        <div className="space-y-3">
          <div className="flex items-center gap-2 border-b border-app-line pb-2">
            <TagIcon className="size-4 text-app-yellow" />
            <h3 className="text-sm font-semibold text-app-text">تفاصيل الاشتراك والحدث</h3>
          </div>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <DetailItem label="خطة الاشتراك" value={record.planName} />
            <DetailItem label="المدرب المسند" value={record.coachesNames} />
            <DetailItem
              label="تاريخ الحدث"
              value={formatDate(record.eventDate)}
              tone="yellow"
            />
            <DetailItem label="تاريخ بداية الاشتراك" value={formatDate(record.startDate)} />
            <DetailItem label="تاريخ نهاية الاشتراك" value={formatDate(record.endDate)} />
            {isFrozen && record.frozenDays > 0 && (
              <DetailItem
                label="عدد أيام التجميد"
                value={`${record.frozenDays.toLocaleString("ar")} يوم`}
                tone="blue"
              />
            )}
            <DetailItem
              label="قيمة الاشتراك الكلية"
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
      </div>
    </Modal>
  );
}
