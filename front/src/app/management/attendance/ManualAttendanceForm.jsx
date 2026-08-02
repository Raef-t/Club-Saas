"use client";

import { useEffect, useMemo, useState } from "react";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import { useGetMembersQuery } from "@/lib/api/membersApi";
import { useGetCoachesQuery } from "@/lib/api/coachesApi";
import { useGetSubscriptionPlansQuery } from "@/lib/api/subscriptionPlansApi";
import { formatLocalizedName } from "@/lib/utils";

const ATTENDABLE_OPTIONS = [
  { value: "member", label: "عضو / لاعب" },
  { value: "staff", label: "موظف" },
];

const MANUAL_MODES = [
  { value: "check-in", label: "دخول" },
  { value: "check-out", label: "انصراف" },
  { value: "bulk", label: "جماعي" },
  { value: "rollback", label: "تراجع" },
];

export default function ManualAttendanceForm({ attendance }) {
  const [manualMode, setManualMode] = useState("check-in");
  const [attendableType, setAttendableType] = useState("member");
  const [attendableId, setAttendableId] = useState("");
  const [checkOutType, setCheckOutType] = useState("member");
  const [checkOutAttendableId, setCheckOutAttendableId] = useState("");
  const [subscriptionPlanId, setSubscriptionPlanId] = useState("");
  const [bulkResult, setBulkResult] = useState(null);
  const [rollbackAttendanceId, setRollbackAttendanceId] = useState("");
  const [rollbackSubscriptionIds, setRollbackSubscriptionIds] = useState("");
  const [pendingRollback, setPendingRollback] = useState(null);
  const peopleQueryParams = attendance.branchId ? { branch_id: attendance.branchId } : {};
  const { currentData: membersResponse, isFetching: isLoadingMembers } = useGetMembersQuery(
    peopleQueryParams,
    { skip: !attendance.branchId },
  );
  const { currentData: coachesResponse, isFetching: isLoadingStaff } = useGetCoachesQuery(
    peopleQueryParams,
    { skip: !attendance.branchId },
  );
  const { currentData: plansResponse, isFetching: isLoadingPlans } =
    useGetSubscriptionPlansQuery(
      { branch_id: attendance.branchId },
      { skip: !attendance.branchId },
    );
  const planOptions = useMemo(
    () =>
      (Array.isArray(plansResponse?.data?.data)
        ? plansResponse.data.data
        : Array.isArray(plansResponse?.data)
          ? plansResponse.data
          : []
      ).map((plan) => ({
        value: String(plan.id),
        label: formatLocalizedName(plan.name),
      })),
    [plansResponse],
  );
  const memberOptions = useMemo(
    () =>
      getCollection(membersResponse).map((member) => ({
        value: String(member.id),
        label:
          member.person?.full_name ||
          [member.person?.first_name, member.person?.last_name].filter(Boolean).join(" ") ||
          `عضو #${member.id}`,
      })),
    [membersResponse],
  );
  const staffOptions = useMemo(
    () =>
      getCollection(coachesResponse).map((coach) => ({
        value: String(coach.staff_id || coach.id),
        label: coach.person?.full_name || coach.full_name || `موظف #${coach.staff_id || coach.id}`,
      })),
    [coachesResponse],
  );
  const checkInPersonOptions = attendableType === "staff" ? staffOptions : memberOptions;
  const checkOutPersonOptions = checkOutType === "staff" ? staffOptions : memberOptions;
  const isLoadingCheckInPeople = attendableType === "staff" ? isLoadingStaff : isLoadingMembers;
  const isLoadingCheckOutPeople = checkOutType === "staff" ? isLoadingStaff : isLoadingMembers;
  const openCheckOutAttendance = useMemo(
    () =>
      attendance.attendanceRows.find(
        (row) =>
          row.isOpen &&
          row.attendableType === checkOutType &&
          String(row.attendableId) === String(checkOutAttendableId) &&
          (!attendance.branchId || String(row.branchId) === String(attendance.branchId)),
      ) || null,
    [attendance.attendanceRows, attendance.branchId, checkOutAttendableId, checkOutType],
  );

  useEffect(() => {
    setSubscriptionPlanId("");
    setBulkResult(null);
    setAttendableId("");
    setCheckOutAttendableId("");
  }, [attendance.branchId]);

  async function submitCheckIn(event) {
    event.preventDefault();
    if (!attendableId) return;

    const succeeded = await attendance.handleManualCheckIn({
      attendableType,
      attendableId,
    });
    if (succeeded) setAttendableId("");
  }

  async function submitCheckOut(event) {
    event.preventDefault();
    if (!openCheckOutAttendance?.id) return;
    const succeeded = await attendance.handleManualCheckOut(openCheckOutAttendance.id);
    if (succeeded) setCheckOutAttendableId("");
  }

  async function submitBulkCheckOut(event) {
    event.preventDefault();
    if (!subscriptionPlanId) return;
    const result = await attendance.handleBulkCheckOut(subscriptionPlanId);
    if (result) setBulkResult(result);
  }

  function submitRollback(event) {
    event.preventDefault();
    if (!rollbackAttendanceId) return;

    const playerSubscriptionIds = [
      ...new Set(
        rollbackSubscriptionIds
          .split(/[\s,،]+/)
          .map(Number)
          .filter((id) => Number.isInteger(id) && id > 0),
      ),
    ];

    setPendingRollback({
      attendanceId: rollbackAttendanceId,
      playerSubscriptionIds,
    });
  }

  async function confirmRollback() {
    if (!pendingRollback) return;
    const succeeded = await attendance.handleRollbackAttendance(
      pendingRollback.attendanceId,
      pendingRollback.playerSubscriptionIds,
    );

    if (succeeded) {
      setRollbackAttendanceId("");
      setRollbackSubscriptionIds("");
      setPendingRollback(null);
    }
  }

  return (
    <section className="w-full rounded-xl border border-app-line bg-app-panel p-4">
      <div className="mb-4 text-right">
        <h2 className="text-lg font-semibold text-app-text">تسجيل يدوي</h2>
        <p className="mt-1 text-xs text-app-muted-light">أدخل بيانات العضو أو الموظف لتسجيل الحركة.</p>
      </div>

      <div className="mb-4 grid grid-cols-4 gap-1 rounded-lg bg-app-card-soft p-1">
        {MANUAL_MODES.map((mode) => (
          <button
            key={mode.value}
            type="button"
            onClick={() => setManualMode(mode.value)}
            className={`rounded-md px-2 py-2 text-xs font-medium transition ${
              manualMode === mode.value
                ? "bg-app-yellow text-black"
                : "text-app-muted-light hover:text-app-text"
            }`}
          >
            {mode.label}
          </button>
        ))}
      </div>

      <div>
        {manualMode === "check-in" && (
        <form onSubmit={submitCheckIn} className="space-y-3 rounded-xl bg-app-card-soft p-4">
          <h3 className="text-sm font-semibold text-app-text">تسجيل دخول</h3>
          <label className="block text-right text-xs text-app-muted-light">
            الفرع
            <Dropdown
              className="mt-2"
              value={attendance.branchId}
              options={attendance.branchOptions}
              onChange={attendance.setBranchId}
              placeholder="اختر الفرع"
              buttonClassName="h-11 bg-app-panel"
              disabled={attendance.isManualCheckingIn}
            />
          </label>
          <Dropdown
            value={attendableType}
            options={ATTENDABLE_OPTIONS}
            onChange={(value) => {
              setAttendableType(value);
              setAttendableId("");
            }}
            buttonClassName="h-11 bg-app-panel"
          />
          <label className="block text-right text-xs text-app-muted-light">
            {attendableType === "staff" ? "الموظف" : "العضو / اللاعب"}
            <Dropdown
              className="mt-2"
              value={attendableId}
              options={checkInPersonOptions}
              onChange={setAttendableId}
              placeholder={isLoadingCheckInPeople ? "جاري التحميل..." : "ابحث بالاسم"}
              buttonClassName="h-11 bg-app-panel"
              searchable
              disabled={!attendance.branchId || isLoadingCheckInPeople}
            />
          </label>
          <Button
            type="submit"
            className="h-11 w-full"
            loading={attendance.isManualCheckingIn}
            disabled={!attendance.branchId || !attendableId}
          >
            تسجيل الدخول
          </Button>
        </form>
        )}

        {manualMode === "check-out" && (
        <form onSubmit={submitCheckOut} className="space-y-3 rounded-xl bg-app-card-soft p-4">
          <h3 className="text-sm font-semibold text-app-text">تسجيل انصراف</h3>
          <Dropdown
            value={checkOutType}
            options={ATTENDABLE_OPTIONS}
            onChange={(value) => {
              setCheckOutType(value);
              setCheckOutAttendableId("");
            }}
            buttonClassName="h-11 bg-app-panel"
          />
          <label className="block text-right text-xs text-app-muted-light">
            {checkOutType === "staff" ? "الموظف" : "العضو / اللاعب"}
            <Dropdown
              className="mt-2"
              value={checkOutAttendableId}
              options={checkOutPersonOptions}
              onChange={setCheckOutAttendableId}
              placeholder={
                isLoadingCheckOutPeople ? "جاري التحميل..." : "ابحث بالاسم"
              }
              buttonClassName="h-11 bg-app-panel"
              searchable
              disabled={!attendance.branchId || isLoadingCheckOutPeople}
            />
          </label>
          {checkOutAttendableId && !openCheckOutAttendance && (
            <p className="text-right text-xs text-app-red">
              لا يوجد سجل حضور مفتوح لهذا الشخص في الفرع المحدد.
            </p>
          )}
          <Button
            type="submit"
            tone="outline"
            className="h-11 w-full"
            loading={attendance.isManualCheckingOut}
            disabled={!openCheckOutAttendance}
          >
            تسجيل الانصراف
          </Button>
        </form>
        )}

        {manualMode === "bulk" && (
        <form
          onSubmit={submitBulkCheckOut}
          className="space-y-3 rounded-xl bg-app-card-soft p-4"
        >
          <h3 className="text-sm font-semibold text-app-text">انصراف جماعي</h3>
          <label className="block text-right text-xs text-app-muted-light">
            خطة الاشتراك
            <Dropdown
              className="mt-2"
              value={subscriptionPlanId}
              options={planOptions}
              onChange={(value) => {
                setSubscriptionPlanId(value);
                setBulkResult(null);
              }}
              placeholder={isLoadingPlans ? "جاري تحميل الخطط..." : "اختر خطة الاشتراك"}
              buttonClassName="h-11 bg-app-panel"
              disabled={!attendance.branchId || isLoadingPlans || attendance.isBulkCheckingOut}
            />
          </label>
          <Button
            type="submit"
            tone="outline"
            className="h-11 w-full"
            loading={attendance.isBulkCheckingOut}
            disabled={!attendance.branchId || !subscriptionPlanId}
          >
            تنفيذ الانصراف الجماعي
          </Button>
          {bulkResult && <BulkResult result={bulkResult} />}
        </form>
        )}

        {manualMode === "rollback" && (
        <form onSubmit={submitRollback} className="space-y-3 rounded-xl bg-app-card-soft p-4">
          <h3 className="text-sm font-semibold text-app-text">التراجع عن الحضور</h3>
          <p className="text-right text-xs leading-5 text-app-muted-light">
            اترك أرقام الاشتراكات فارغة لإرجاع جميع الجلسات وحذف سجل الحضور بالكامل.
          </p>
          <ManualNumberField
            label="رقم سجل الحضور (Attendance ID)"
            value={rollbackAttendanceId}
            onChange={setRollbackAttendanceId}
          />
          <label className="block text-right text-xs text-app-muted-light">
            أرقام اشتراكات اللاعب (اختياري)
            <input
              type="text"
              value={rollbackSubscriptionIds}
              onChange={(event) => setRollbackSubscriptionIds(event.target.value)}
              placeholder="مثال: 7, 9, 12"
              className="app-input mt-2 h-11 w-full px-3 text-right text-app-text outline-none focus:border-app-yellow"
            />
          </label>
          <Button
            type="submit"
            tone="danger"
            className="h-11 w-full"
            disabled={!rollbackAttendanceId || attendance.isRollingBack}
          >
            التراجع وإرجاع الجلسات
          </Button>
        </form>
        )}
      </div>

      <ConfirmDialog
        open={Boolean(pendingRollback)}
        onClose={() => setPendingRollback(null)}
        onConfirm={confirmRollback}
        title="تأكيد التراجع عن الحضور"
        message={
          pendingRollback?.playerSubscriptionIds.length
            ? `سيتم إرجاع الجلسات للاشتراكات المحددة من سجل الحضور #${pendingRollback.attendanceId}.`
            : `سيتم إرجاع جميع الجلسات وحذف سجل الحضور #${pendingRollback?.attendanceId || ""} بالكامل.`
        }
        confirmLabel="تأكيد التراجع"
        isLoading={attendance.isRollingBack}
      />
    </section>
  );
}

function BulkResult({ result }) {
  return (
    <div className="grid grid-cols-3 gap-2 rounded-lg border border-app-line p-3 text-center text-xs">
      <ResultCount label="المعالج" value={result.total_processed} />
      <ResultCount label="الناجح" value={result.success_count} tone="text-app-green" />
      <ResultCount label="الفاشل" value={result.failed_count} tone="text-app-red" />
    </div>
  );
}

function ResultCount({ label, value, tone = "text-app-text" }) {
  return (
    <div>
      <div className={`text-base font-semibold ${tone}`}>{Number(value || 0).toLocaleString("ar")}</div>
      <div className="text-app-muted-light">{label}</div>
    </div>
  );
}

function ManualNumberField({ label, value, onChange }) {
  return (
    <label className="block text-right text-xs text-app-muted-light">
      {label}
      <input
        type="number"
        min="1"
        required
        value={value}
        onChange={(event) => onChange(event.target.value)}
        className="app-input mt-2 h-11 w-full px-3 text-right text-app-text outline-none focus:border-app-yellow"
      />
    </label>
  );
}

function getCollection(response) {
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  return [];
}
