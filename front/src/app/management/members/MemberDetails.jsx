import DetailItem from "@/components/ui/DetailItem";
import { formatDate, formatLocalizedName } from "@/lib/utils";
import {
  MEMBER_GENDER_LABELS as genderLabels,
  RELATION_LABELS as relationLabels,
} from "./memberConstants";
export default function MemberDetails({ member, branches = [] }) {
  if (!member) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل لهذا العضو.
      </div>
    );
  }

  const person = member.person || {};
  const branchName =
    formatLocalizedName(branches.find((b) => b.id === member.branch_id)?.name) ||
    `فرع #${member.branch_id}`;

  const personalContact = person.contacts?.find(
    (c) => c.relation === "self" || c.name === "Personal",
  );
  const emergencyContact = person.contacts?.find((c) => c.relation !== "self");

  const fullName =
    person.full_name || `${member.first_name || ""} ${member.last_name || ""}`.trim() || "-";
  const gender = person.gender || member.gender || "-";
  const mobile =
    personalContact?.phone_number || person.phone || person.mobile || member.mobile || "";
  const countryCode =
    personalContact?.country_code || person.mobile_country_code || member.mobile_country_code || "";
  const dob = person.dob || member.dob || "";
  const age = person.age || member.age || "";

  return (
    <div className="space-y-6">
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right">
        <h3 className="text-lg font-medium text-white">{fullName}</h3>
        <p className="mt-1 text-xs text-app-muted-light">رقم العضوية: #{member.id}</p>
      </div>

      <section className="grid gap-3 sm:grid-cols-2">
        <DetailItem label="الفرع" value={branchName} tone="yellow" />
        <DetailItem label="الجنس" value={genderLabels[gender] || gender} />
        <DetailItem label="الهاتف" value={mobile ? `${countryCode} ${mobile}`.trim() : "-"} />
        <DetailItem label="تاريخ الميلاد" value={formatDate(dob)} />
        <DetailItem label="العمر" value={age ? `${age} سنة` : "-"} />
        <DetailItem
          label="حالة الاشتراك"
          value={member.is_active !== false ? "نشط" : "غير نشط"}
          tone={member.is_active !== false ? "green" : "red"}
        />
        <DetailItem label="تاريخ التسجيل" value={formatDate(member.created_at)} />
      </section>

      {/* Emergency Contact */}
      {emergencyContact && (
        <div className="rounded-xl border border-app-line bg-app-card-soft/50 p-4 text-right space-y-3">
          <h4 className="text-sm font-semibold text-white">جهة اتصال إضافية للطوارئ</h4>
          <section className="grid gap-3 sm:grid-cols-2">
            <DetailItem label="اسم القريب" value={emergencyContact.name} />
            <DetailItem
              label="العلاقة"
              value={relationLabels[emergencyContact.relation] || emergencyContact.relation}
            />
            <DetailItem
              label="رقم الهاتف"
              value={
                emergencyContact.phone_number
                  ? `${emergencyContact.country_code || ""} ${emergencyContact.phone_number}`
                  : "-"
              }
            />
          </section>
        </div>
      )}
    </div>
  );
}
