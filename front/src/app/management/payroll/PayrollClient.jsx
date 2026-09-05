"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import Link from "next/link";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import DataTable from "@/components/ui/DataTable";
import Dropdown from "@/components/ui/Dropdown";
import StatsGrid from "@/components/ui/StatsGrid";
import { useToast } from "@/components/ui/Toast";
import {
  CalendarIcon,
  CheckIcon,
  HandCoinsIcon,
  PencilIcon,
  PlusIcon,
} from "@/components/icons/Icons";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { useGetBranchSettingsQuery, useUpdateBranchSettingsMutation } from "@/lib/api/branchesApi";
import {
  useConfirmPayslipsMutation,
  useGeneratePayslipsMutation,
  useGetPayslipsQuery,
  useUpdatePayslipMutation,
} from "@/lib/api/payslipsApi";
import { getApiErrorMessage } from "@/lib/apiError";
import { formatDate, formatMoney } from "@/lib/utils";
import {
  createBranchSettingsForm,
  createBranchSettingsPayload,
  getSettingsRecord,
} from "@/app/management/settings/settingsUtils";
import PayslipEditorModal from "./PayslipEditorModal";
import {
  createConfirmPayload,
  createUpdatePayload,
  filterPayslipsByBranch,
  getPayrollDraft,
  getPayrollEndDay,
  getPayrollPeriodLabel,
  getPayslipStaffName,
  getPayslips,
  normalizePayslip,
  updateDraftPayslip,
} from "./payrollUtils";

const DRAFT_PAYROLL_TABLE_GRID =
  "48px minmax(130px,1.3fr) minmax(110px,.9fr) minmax(84px,.75fr) minmax(60px,.55fr) minmax(80px,.7fr) minmax(80px,.7fr) minmax(96px,.85fr) 154px";
const SAVED_PAYROLL_TABLE_GRID =
  "48px minmax(130px,1.3fr) minmax(80px,.8fr) minmax(80px,.8fr) minmax(60px,.6fr) minmax(75px,.7fr) minmax(75px,.7fr) minmax(90px,.85fr) 110px 105px";
const PAYROLL_DAY_OPTIONS = Array.from({ length: 31 }, (_, index) => ({
  value: String(index + 1),
  label: `اليوم ${index + 1}`,
}));

export default function PayrollClient({ initialAction = null }) {
  const toast = useToast();
  const { branches, selectedBranchId, selectedBranch, isAllBranches, setSelectedBranchId } =
    useManagementBranch();
  const [activeTab, setActiveTab] = useState("draft");
  const [draft, setDraft] = useState(null);
  const [editing, setEditing] = useState(null);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [selectedPayrollDay, setSelectedPayrollDay] = useState("");
  const [generationRequest, setGenerationRequest] = useState(null);
  const generationSequenceRef = useRef(0);
  const processedActionRef = useRef(null);
  const branchId = isAllBranches ? "" : selectedBranchId;

  const {
    data: settingsResponse,
    error: settingsError,
    isLoading: isLoadingSettings,
    isFetching: isFetchingSettings,
    refetch: refetchSettings,
  } = useGetBranchSettingsQuery(branchId, { skip: !branchId });
  const {
    data: savedResponse,
    error: savedError,
    isLoading: isLoadingSaved,
    isFetching: isFetchingSaved,
    refetch: refetchSaved,
  } = useGetPayslipsQuery(branchId ? { branch_id: branchId } : undefined);
  const [generatePayslips, { isLoading: isGenerating }] = useGeneratePayslipsMutation();
  const [updateBranchSettings, { isLoading: isSavingPayrollDay }] =
    useUpdateBranchSettingsMutation();
  const [updatePayslip, { isLoading: isUpdating }] = useUpdatePayslipMutation();
  const [confirmPayslips, { isLoading: isConfirming }] = useConfirmPayslipsMutation();

  const payrollEndDay = getPayrollEndDay(settingsResponse);
  const savedPayslips = useMemo(
    () =>
      filterPayslipsByBranch(getPayslips(savedResponse).map(normalizePayslip), selectedBranchId),
    [savedResponse, selectedBranchId],
  );

  useEffect(() => {
    generationSequenceRef.current += 1;
    setDraft(null);
    setGenerationRequest(null);
    setEditing(null);
    setConfirmOpen(false);
  }, [selectedBranchId]);

  useEffect(() => {
    setSelectedPayrollDay(payrollEndDay ? String(payrollEndDay) : "");
  }, [payrollEndDay]);

  const actionKey = initialAction
    ? [
        initialAction.type,
        initialAction.branchId,
        initialAction.notificationId,
        initialAction.periodStart,
        initialAction.periodEnd,
      ].join(":")
    : "";

  useEffect(() => {
    if (!initialAction || processedActionRef.current === actionKey || branches.length === 0) return;

    const actionBranchExists = branches.some(
      (branch) => String(branch.id) === String(initialAction.branchId),
    );
    if (!actionBranchExists) {
      processedActionRef.current = actionKey;
      toast.error("الفرع المرتبط بإشعار الرواتب غير متاح لهذا الحساب.");
      return;
    }

    if (String(selectedBranchId) !== String(initialAction.branchId)) {
      setSelectedBranchId(initialAction.branchId);
      return;
    }

    processedActionRef.current = actionKey;
    void loadDraft(initialAction.branchId, {
      source: "notification",
      periodStart: initialAction.periodStart,
      periodEnd: initialAction.periodEnd,
    });
  }, [actionKey, branches, initialAction, selectedBranchId, setSelectedBranchId, toast]);

  const displayedPayslips = activeTab === "draft" ? draft?.payslips || [] : savedPayslips;
  const totals = useMemo(
    () =>
      displayedPayslips.reduce(
        (result, payslip) => ({
          net: result.net + (Number(payslip.net_pay) || 0),
          bonuses: result.bonuses + (Number(payslip.bonuses) || 0),
          deductions: result.deductions + (Number(payslip.deductions) || 0),
        }),
        { net: 0, bonuses: 0, deductions: 0 },
      ),
    [displayedPayslips],
  );

  async function handlePayrollDayChange(day) {
    setSelectedPayrollDay(day);
    setDraft(null);

    if (!branchId) {
      toast.warning("اختر فرعاً محدداً لتوليد مسودة الرواتب.");
      return;
    }

    const dayNumber = Number(day);
    if (!Number.isInteger(dayNumber) || dayNumber < 1 || dayNumber > 31) {
      toast.error("اختر يوماً صحيحاً من 1 إلى 31.");
      return;
    }

    try {
      const settingsRecord = getSettingsRecord(settingsResponse);
      const settingsPayload = createBranchSettingsPayload(createBranchSettingsForm(settingsRecord));

      await updateBranchSettings({
        branchId,
        body: {
          ...settingsPayload,
          payroll_end_day: dayNumber,
        },
      }).unwrap();

      toast.success("تم حفظ يوم إقفال الرواتب.");
    } catch (error) {
      toast.error(getApiErrorMessage(error, "تعذر حفظ يوم إقفال الرواتب."));
    }
  }

  async function loadDraft(targetBranchId, context = { source: "manual" }) {
    const numericBranchId = Number(targetBranchId);
    if (!Number.isInteger(numericBranchId) || numericBranchId <= 0) {
      toast.warning("اختر فرعاً محدداً لتوليد مسودة الرواتب.");
      return;
    }

    const generationSequence = generationSequenceRef.current + 1;
    generationSequenceRef.current = generationSequence;
    setActiveTab("draft");
    setDraft(null);
    setGenerationRequest({ branchId: String(targetBranchId), ...context });

    try {
      const response = await generatePayslips({ branch_id: numericBranchId }).unwrap();
      if (generationSequence !== generationSequenceRef.current) return;

      const nextDraft = getPayrollDraft(response);
      if (!nextDraft) throw new Error("لم تُرجع الخدمة مسودة رواتب صالحة.");

      setDraft(nextDraft);
      toast.success(
        context.source === "notification"
          ? "تم جلب مسودة الرواتب من الإشعار وهي جاهزة للمراجعة."
          : "تم توليد مسودة الرواتب وهي جاهزة للمراجعة.",
      );
    } catch (error) {
      if (generationSequence !== generationSequenceRef.current) return;
      setGenerationRequest(null);
      toast.error(getApiErrorMessage(error, error?.message || "تعذر توليد مسودة الرواتب."));
    }
  }

  async function handleSaveEdit(changes) {
    if (!editing) return;

    if (editing.source === "draft") {
      setDraft((current) => ({
        ...current,
        payslips: current.payslips.map((payslip) =>
          payslip === editing.payslip ||
          String(payslip.staff_id) === String(editing.payslip.staff_id)
            ? updateDraftPayslip(payslip, changes)
            : payslip,
        ),
      }));
      setEditing(null);
      toast.success("تم تحديث مسودة الراتب.");
      return;
    }

    if (!editing.payslip.id) {
      toast.error("لا يمكن تعديل السجل المحفوظ لعدم توفر معرّفه.");
      return;
    }

    try {
      await updatePayslip({
        id: editing.payslip.id,
        body: createUpdatePayload(changes),
      }).unwrap();
      setEditing(null);
      toast.success("تم تعديل سجل الراتب بنجاح.");
    } catch (error) {
      toast.error(getApiErrorMessage(error, "تعذر تعديل سجل الراتب."));
    }
  }

  async function handleConfirm() {
    if (!branchId || !draft?.payslips?.length) return;
    try {
      const response = await confirmPayslips(createConfirmPayload(branchId, draft)).unwrap();
      setConfirmOpen(false);
      setDraft(null);
      setGenerationRequest(null);
      setActiveTab("saved");
      toast.success(response?.message || "تم تثبيت واعتماد الرواتب بنجاح.");
    } catch (error) {
      toast.error(getApiErrorMessage(error, "تعذر تثبيت واعتماد الرواتب."));
    }
  }

  const columns = useMemo(
    () =>
      [
        { key: "rowNumber", label: "#", type: "rowNumber", align: "center", sortable: false },
        {
          key: "staff_name",
          label: "الموظف",
          align: "start",
          sortValue: getPayslipStaffName,
          render: (_, payslip) => (
            <div className="min-w-0 px-2 text-start">
              <p className="truncate text-sm font-medium text-app-text">
                {getPayslipStaffName(payslip)}
              </p>
            </div>
          ),
        },
        {
          key: "base_pay",
          label: "الراتب الأساسي",
          align: "center",
          render: (value, payslip) =>
            activeTab === "draft" && !isCommissionBased(payslip) ? (
              <input
                type="number"
                min="0"
                step="0.01"
                className="app-input mx-auto h-9 w-24 px-2 text-center text-xs text-app-text"
                value={value}
                onChange={(event) => {
                  const nextBasePay = event.target.value;
                  setDraft((current) => ({
                    ...current,
                    payslips: current.payslips.map((item) =>
                      item === payslip ? updateDraftPayslip(item, { base_pay: nextBasePay }) : item,
                    ),
                  }));
                }}
                aria-label={`الراتب الأساسي لـ ${getPayslipStaffName(payslip)}`}
              />
            ) : isCommissionBased(payslip) ? (
              <EmptyMoney />
            ) : (
              <Money value={value} />
            ),
        },
        {
          key: "commission_pay",
          label: "النسبة",
          align: "center",
          render: (value, payslip) =>
            isFixedSalary(payslip) ? <EmptyMoney /> : <Money value={value} />,
        },
        {
          key: "subscribers_count",
          label: "المشتركون",
          align: "center",
          render: (value) => <span>{Number(value || 0).toLocaleString("ar")}</span>,
        },
        {
          key: "bonuses",
          label: "المكافآت",
          align: "center",
          render: (value) => <Money value={value} className="text-app-green" />,
        },
        {
          key: "deductions",
          label: "الخصومات",
          align: "center",
          render: (value) => <Money value={value} className="text-app-red" />,
        },
        {
          key: "net_pay",
          label: "صافي الراتب",
          align: "center",
          render: (value) => <Money value={value} className="font-semibold text-app-yellow" />,
        },
        {
          key: "status",
          label: "حالة الصرف",
          align: "center",
          render: (_, payslip) => {
            const isPaid = payslip?.status === "paid";
            const journalId =
              payslip?.salary_payments?.[0]?.journal_id ||
              payslip?.salary_payment?.journal_id ||
              payslip?.salary_payments?.[0]?.id;

            return (
              <div className="flex flex-col items-center gap-0.5">
                <span
                  className={`inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-medium ${
                    isPaid
                      ? "bg-app-green/15 text-app-green border border-app-green/30"
                      : "bg-app-yellow/15 text-app-yellow border border-app-yellow/30"
                  }`}
                >
                  {isPaid ? "مدفوع" : "بانتظار الصرف"}
                </span>
                {isPaid && journalId && (
                  <span className="text-[10px] text-app-muted-light">سند #{journalId}</span>
                )}
              </div>
            );
          },
        },
        {
          key: "actions",
          label: "الإجراء",
          align: "center",
          sortable: false,
          render: (_, payslip) => {
            if (activeTab === "draft") {
              return (
                <div className="flex items-center justify-center gap-1">
                  <DraftActionButton
                    label="تعديل التفاصيل"
                    onClick={() => setEditing({ source: activeTab, payslip })}
                  >
                    <PencilIcon className="size-3.5" />
                  </DraftActionButton>
                  <DraftActionButton
                    label="إضافة مكافأة"
                    tone="green"
                    onClick={() =>
                      setEditing({ source: activeTab, payslip, initialAdjustmentType: "bonus" })
                    }
                  >
                    <PlusIcon className="size-3.5" />
                  </DraftActionButton>
                  <DraftActionButton
                    label="إضافة خصم"
                    tone="red"
                    onClick={() =>
                      setEditing({ source: activeTab, payslip, initialAdjustmentType: "deduction" })
                    }
                  >
                    <MinusIcon className="size-3.5" />
                  </DraftActionButton>
                </div>
              );
            }

            const isPaid = payslip?.status === "paid";
            if (isPaid) {
              return (
                <span className="inline-flex h-8 items-center px-2 text-xs text-app-muted-light/60">
                  تم الصرف
                </span>
              );
            }

            return (
              <Link
                href={`/accounting/salaries?openModal=true&payslip_id=${payslip.id}&staff_id=${payslip.staff_id}&amount=${payslip.net_pay}`}
                className="inline-flex h-8 items-center gap-1.5 rounded-lg border border-app-yellow/40 bg-app-yellow/10 px-2.5 text-xs font-medium text-app-yellow transition hover:bg-app-yellow hover:text-black"
                title="صرف القسيمة عبر قسم المحاسبة"
              >
                <HandCoinsIcon className="size-3.5" />
                صرف نقدي
              </Link>
            );
          },
        },
      ].filter((column) => {
        if (activeTab === "draft") return column.key !== "status";
        return true;
      }),
    [activeTab],
  );

  const selectedName =
    typeof selectedBranch?.name === "string"
      ? selectedBranch.name
      : selectedBranch?.name?.ar || selectedBranch?.name?.en || "الفرع المختار";

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النادي"
        title="الرواتب"
        subtitle="توليد مسودة الرواتب ومراجعة الخصومات والمكافآت قبل تثبيتها وحفظها."
      />

      <section className="rounded-2xl border border-app-line bg-app-panel p-4 shadow-sm sm:p-5">
        <div className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
          <div className="grid flex-1 gap-4 sm:grid-cols-2">
            <div>
              <p className="mb-2 text-xs font-medium text-app-muted-light">الفرع</p>
              <div className="app-input flex h-11 items-center px-3 text-sm text-app-text">
                {isAllBranches ? "اختر فرعاً من القائمة العلوية" : selectedName}
              </div>
            </div>
            <div>
              <p className="mb-2 text-xs font-medium text-app-muted-light">يوم إقفال الرواتب</p>
              <Dropdown
                value={selectedPayrollDay}
                options={PAYROLL_DAY_OPTIONS}
                onChange={handlePayrollDayChange}
                icon={CalendarIcon}
                disabled={
                  !branchId ||
                  isLoadingSettings ||
                  isFetchingSettings ||
                  Boolean(settingsError) ||
                  isGenerating ||
                  isSavingPayrollDay
                }
                placeholder={
                  !branchId
                    ? "اختر فرعاً أولاً"
                    : isLoadingSettings || isFetchingSettings
                      ? "جاري تحميل الإعدادات..."
                      : "اختر يوم الإقفال"
                }
                className="text-app-text"
                buttonClassName="h-11 border border-app-line bg-app-card-soft"
              />
              {(isSavingPayrollDay || isGenerating) && (
                <p className="mt-2 text-xs text-app-yellow" role="status">
                  {isSavingPayrollDay ? "جاري حفظ يوم الإقفال..." : "جاري توليد الرواتب..."}
                </p>
              )}
              {settingsError && !isLoadingSettings && (
                <button
                  type="button"
                  className="mt-2 text-xs text-app-red underline underline-offset-2"
                  onClick={refetchSettings}
                >
                  تعذر تحميل إعدادات الفرع — إعادة المحاولة
                </button>
              )}
            </div>
          </div>
        </div>

        <div className="mt-4 flex flex-wrap items-center gap-2 border-t border-app-line pt-4 text-xs text-app-muted-light">
          <span className="rounded-full bg-app-yellow-soft px-3 py-1 text-app-yellow">
            اليوم المختار: {selectedPayrollDay || "-"} من 31
          </span>
          <span>
            الفترة الحالية: {getPayrollPeriodLabel(draft?.period_start, draft?.period_end)}
          </span>
        </div>
      </section>

      {generationRequest?.source === "notification" && (isGenerating || draft) && (
        <section
          className="flex flex-col gap-3 rounded-2xl border border-app-yellow/30 bg-app-yellow/[0.06] px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
          role="status"
        >
          <div>
            <p className="text-sm font-medium text-app-yellow">
              {isGenerating
                ? "جاري جلب مسودة الرواتب من الإشعار..."
                : "تم فتح مسودة استحقاق الرواتب"}
            </p>
            <p className="mt-1 text-xs text-app-muted-light">
              {generationRequest.periodStart && generationRequest.periodEnd
                ? `الفترة الواردة في الإشعار: ${formatDate(generationRequest.periodStart)} إلى ${formatDate(generationRequest.periodEnd)}`
                : "راجع القيم أدناه وعدّلها قبل التثبيت والاعتماد."}
            </p>
          </div>
          {draft && (
            <span className="w-fit rounded-full border border-app-green/30 bg-app-green/10 px-3 py-1 text-xs text-app-green">
              جاهزة للمراجعة
            </span>
          )}
        </section>
      )}

      <div className="flex w-fit rounded-xl border border-app-line bg-app-panel p-1">
        <TabButton active={activeTab === "draft"} onClick={() => setActiveTab("draft")}>
          مسودة الرواتب
          {draft?.payslips?.length ? <Count value={draft.payslips.length} /> : null}
        </TabButton>
        <TabButton active={activeTab === "saved"} onClick={() => setActiveTab("saved")}>
          الرواتب المثبتة
          <Count value={savedPayslips.length} />
        </TabButton>
      </div>

      <StatsGrid
        variant="compact"
        items={[
          {
            title: "عدد السجلات",
            value: displayedPayslips.length.toLocaleString("ar"),
            tone: "blue",
            iconKey: "members",
            compact: true,
          },
          {
            title: "إجمالي الصافي",
            value: formatMoney(totals.net),
            tone: "yellow",
            iconKey: "subscriptions",
            compact: true,
          },
          {
            title: "إجمالي المكافآت",
            value: formatMoney(totals.bonuses),
            tone: "green",
            iconKey: "subscriptions",
            compact: true,
          },
          {
            title: "إجمالي الخصومات",
            value: formatMoney(totals.deductions),
            tone: "orange",
            iconKey: "subscriptions",
            compact: true,
          },
        ]}
      />

      <DataTable
        title={activeTab === "draft" ? "مسودة الرواتب" : "سجل الرواتب المثبتة والمحفوظة"}
        subtitle={
          activeTab === "draft"
            ? draft
              ? `فترة الرواتب: ${formatDate(draft.period_start)} إلى ${formatDate(draft.period_end)}`
              : "اختر يوم الإقفال، ثم افتح المسودة من إشعار التوليد."
            : "الرواتب التي تم تثبيتها وحفظها في قاعدة البيانات."
        }
        columns={columns}
        rows={displayedPayslips}
        tableColumns={activeTab === "draft" ? DRAFT_PAYROLL_TABLE_GRID : SAVED_PAYROLL_TABLE_GRID}
        minWidth="0px"
        desktopScrollable={false}
        showAdd={false}
        showSearch={false}
        showFilter={false}
        showExport={false}
        pageSize={10}
        pageSizeOptions={[10, 20, 50]}
        isLoading={activeTab === "saved" && isLoadingSaved}
        getRowKey={(payslip, index) => payslip.id || `${payslip.staff_id}-${index}`}
        emptyMessage={
          activeTab === "draft" ? (
            isGenerating ? (
              "جاري جلب مسودة الرواتب وحساب العمولات والسلف..."
            ) : (
              "لا توجد مسودة معروضة حالياً. افتح إشعار استحقاق الرواتب لتوليدها ومراجعتها."
            )
          ) : savedError ? (
            <div className="space-y-3 text-center">
              <p className="text-app-red">تعذر تحميل الرواتب المثبتة.</p>
              <Button tone="outline" className="h-9 px-3 text-xs" onClick={refetchSaved}>
                إعادة المحاولة
              </Button>
            </div>
          ) : (
            "لا توجد رواتب مثبتة ومحفوظة حتى الآن."
          )
        }
        toolbarMeta={
          activeTab === "draft" && draft?.payslips?.length ? (
            <Button
              onClick={() => setConfirmOpen(true)}
              icon={<CheckIcon className="size-4" />}
              className="h-10"
            >
              تثبيت واعتماد الرواتب
            </Button>
          ) : activeTab === "saved" && isFetchingSaved && !isLoadingSaved ? (
            <span className="text-xs text-app-yellow">جاري تحديث السجلات...</span>
          ) : null
        }
      />

      <PayslipEditorModal
        payslip={editing?.payslip}
        onClose={() => setEditing(null)}
        onSave={handleSaveEdit}
        isSaving={isUpdating}
        initialAdjustmentType={editing?.initialAdjustmentType}
      />

      <ConfirmDialog
        open={confirmOpen}
        onClose={() => setConfirmOpen(false)}
        onConfirm={handleConfirm}
        title="تثبيت واعتماد الرواتب"
        message={`سيتم اعتماد ${draft?.payslips?.length || 0} سجل راتب بصافي إجمالي ${formatMoney(totals.net)}. بعد التثبيت ستظهر السجلات ضمن الرواتب المثبتة.`}
        confirmLabel="تثبيت واعتماد"
        tone="primary"
        isLoading={isConfirming}
      />
    </div>
  );
}

function Money({ value, className = "text-app-text" }) {
  return <span className={`whitespace-nowrap text-xs ${className}`}>{formatMoney(value)}</span>;
}

function EmptyMoney() {
  return <span className="text-sm text-app-muted-light">-</span>;
}

function getEmploymentType(payslip) {
  return payslip?.employment_type || payslip?.staff?.employment_type || "";
}

function isCommissionBased(payslip) {
  const employmentType = getEmploymentType(payslip);
  if (employmentType) return employmentType === "commission_based";
  return Number(payslip?.commission_pay) > 0 && Number(payslip?.base_pay) <= 0;
}

function isFixedSalary(payslip) {
  const employmentType = getEmploymentType(payslip);
  if (employmentType) return employmentType === "fixed_salary";
  return Number(payslip?.commission_pay) <= 0;
}

function TabButton({ active, onClick, children }) {
  return (
    <button
      type="button"
      className={`flex h-10 items-center gap-2 rounded-lg px-4 text-sm transition ${
        active
          ? "bg-app-yellow font-medium text-app-on-accent"
          : "text-app-muted-light hover:bg-app-card-soft hover:text-app-text"
      }`}
      onClick={onClick}
    >
      {children}
    </button>
  );
}

function Count({ value }) {
  return (
    <span className="rounded-full bg-black/10 px-2 py-0.5 text-[10px]">
      {Number(value).toLocaleString("ar")}
    </span>
  );
}

function DraftActionButton({ label, tone = "default", onClick, children }) {
  const toneClass = {
    default:
      "border-app-line text-app-muted-light hover:border-app-yellow/60 hover:text-app-yellow",
    green: "border-app-green/25 bg-app-green/10 text-app-green hover:border-app-green/60",
    red: "border-app-red/25 bg-app-red/10 text-app-red hover:border-app-red/60",
  }[tone];

  return (
    <button
      type="button"
      className={`grid size-8 place-items-center rounded-lg border transition ${toneClass}`}
      onClick={onClick}
      title={label}
      aria-label={label}
    >
      {children}
    </button>
  );
}

function MinusIcon({ className = "size-4" }) {
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
    >
      <path d="M5 12h14" strokeLinecap="round" />
    </svg>
  );
}
