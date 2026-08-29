"use client";

import { useEffect, useMemo, useState } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import DataTable from "@/components/ui/DataTable";
import Dropdown from "@/components/ui/Dropdown";
import StatsGrid from "@/components/ui/StatsGrid";
import { useToast } from "@/components/ui/Toast";
import { CalendarIcon, CheckIcon, PencilIcon } from "@/components/icons/Icons";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { useGetBranchSettingsQuery } from "@/lib/api/branchesApi";
import {
  useConfirmPayslipsMutation,
  useGeneratePayslipsMutation,
  useGetPayslipsQuery,
  useUpdatePayslipMutation,
} from "@/lib/api/payslipsApi";
import { getApiErrorMessage } from "@/lib/apiError";
import { formatDate, formatMoney } from "@/lib/utils";
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
  "48px minmax(130px,1.4fr) minmax(84px,.85fr) minmax(84px,.85fr) minmax(60px,.6fr) minmax(80px,.75fr) minmax(80px,.75fr) minmax(96px,.9fr) 72px";
const SAVED_PAYROLL_TABLE_GRID =
  "48px minmax(140px,1.4fr) minmax(84px,.85fr) minmax(84px,.85fr) minmax(60px,.6fr) minmax(80px,.75fr) minmax(80px,.75fr) minmax(96px,.9fr) 110px";

export default function PayrollClient() {
  const toast = useToast();
  const { selectedBranchId, selectedBranch, isAllBranches } = useManagementBranch();
  const [activeTab, setActiveTab] = useState("draft");
  const [draft, setDraft] = useState(null);
  const [editing, setEditing] = useState(null);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [selectedPayrollDay, setSelectedPayrollDay] = useState("");
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
  } = useGetPayslipsQuery();
  const [generatePayslips, { isLoading: isGenerating }] = useGeneratePayslipsMutation();
  const [updatePayslip, { isLoading: isUpdating }] = useUpdatePayslipMutation();
  const [confirmPayslips, { isLoading: isConfirming }] = useConfirmPayslipsMutation();

  const payrollEndDay = getPayrollEndDay(settingsResponse);
  const payrollDayOptions = useMemo(
    () =>
      payrollEndDay
        ? Array.from({ length: payrollEndDay }, (_, index) => ({
            value: String(index + 1),
            label: `اليوم ${index + 1}`,
          }))
        : [],
    [payrollEndDay],
  );
  const savedPayslips = useMemo(
    () =>
      filterPayslipsByBranch(getPayslips(savedResponse).map(normalizePayslip), selectedBranchId),
    [savedResponse, selectedBranchId],
  );

  useEffect(() => {
    setDraft(null);
    setEditing(null);
    setConfirmOpen(false);
  }, [selectedBranchId]);

  useEffect(() => {
    setSelectedPayrollDay(payrollEndDay ? String(payrollEndDay) : "");
  }, [payrollEndDay]);

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

  async function handleGenerate() {
    if (!branchId) {
      toast.warning("اختر فرعاً محدداً لتوليد مسودة الرواتب.");
      return;
    }
    if (!payrollEndDay) {
      toast.error("لم يتم العثور على يوم إقفال الرواتب في إعدادات الفرع.");
      return;
    }

    try {
      const response = await generatePayslips({ branch_id: Number(branchId) }).unwrap();
      const nextDraft = getPayrollDraft(response);
      if (!nextDraft) throw new Error("لم تُرجع الخدمة مسودة رواتب صالحة.");
      setDraft(nextDraft);
      setActiveTab("draft");
      toast.success(response?.message || "تم حساب مسودة الرواتب بنجاح");
    } catch (error) {
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
      setActiveTab("saved");
      toast.success(response?.message || "تم تثبيت وحفظ الرواتب بنجاح.");
    } catch (error) {
      toast.error(getApiErrorMessage(error, "تعذر تثبيت وحفظ الرواتب."));
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
            isCommissionBased(payslip) ? <EmptyMoney /> : <Money value={value} />,
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
            return (
              <span
                className={`inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-medium ${
                  isPaid
                    ? "bg-app-green/15 text-app-green border border-app-green/30"
                    : "bg-app-yellow/15 text-app-yellow border border-app-yellow/30"
                }`}
              >
                {isPaid ? "مدفوع" : "بانتظار الصرف"}
              </span>
            );
          },
        },
        {
          key: "actions",
          label: "الإجراء",
          align: "center",
          sortable: false,
          render: (_, payslip) => (
            <button
              type="button"
              className="inline-flex h-8 items-center gap-1.5 rounded-lg border border-app-line bg-app-card px-2.5 text-xs text-app-muted-light transition hover:border-app-yellow/60 hover:text-app-yellow"
              onClick={() => setEditing({ source: activeTab, payslip })}
            >
              <PencilIcon className="size-3.5" />
              تعديل
            </button>
          ),
        },
      ].filter((column) => {
        if (activeTab === "draft") return column.key !== "status";
        return column.key !== "actions";
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
                options={payrollDayOptions}
                onChange={setSelectedPayrollDay}
                icon={CalendarIcon}
                disabled={!branchId || isLoadingSettings || isFetchingSettings || !payrollEndDay}
                placeholder={
                  !branchId
                    ? "اختر فرعاً أولاً"
                    : isLoadingSettings || isFetchingSettings
                      ? "جاري تحميل الإعدادات..."
                      : "غير محدد في إعدادات الفرع"
                }
                className="text-app-text"
                buttonClassName="h-11 border border-app-line bg-app-card-soft"
              />
              {settingsError && (
                <button
                  type="button"
                  className="mt-2 text-xs text-app-red underline underline-offset-2"
                  onClick={refetchSettings}
                >
                  تعذر تحميل الإعدادات — إعادة المحاولة
                </button>
              )}
            </div>
          </div>

          <Button
            className="h-11 min-w-44"
            onClick={handleGenerate}
            loading={isGenerating}
            loadingLabel="جاري توليد المسودة"
            disabled={!branchId || !payrollEndDay || !selectedPayrollDay}
          >
            توليد مسودة الرواتب
          </Button>
        </div>

        <div className="mt-4 flex flex-wrap items-center gap-2 border-t border-app-line pt-4 text-xs text-app-muted-light">
          <span className="rounded-full bg-app-yellow-soft px-3 py-1 text-app-yellow">
            اليوم المختار: {selectedPayrollDay || "-"} من {payrollEndDay || "-"}
          </span>
          <span>
            الفترة الحالية: {getPayrollPeriodLabel(draft?.period_start, draft?.period_end)}
          </span>
        </div>
      </section>

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
              : "ولّد المسودة لمراجعة رواتب الموظفين قبل التثبيت."
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
            "لا توجد مسودة حالياً. اختر فرعاً ثم اضغط «توليد مسودة الرواتب»."
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
              تثبيت وحفظ الرواتب
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
      />

      <ConfirmDialog
        open={confirmOpen}
        onClose={() => setConfirmOpen(false)}
        onConfirm={handleConfirm}
        title="تثبيت وحفظ الرواتب"
        message={`سيتم تثبيت ${draft?.payslips?.length || 0} سجل راتب للفترة المحددة، وبعدها ستظهر ضمن الرواتب المثبتة.`}
        confirmLabel="تثبيت وحفظ"
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
