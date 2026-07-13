"use client";

import { useState, useEffect, useMemo } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import { Field } from "@/components/forms/FormControls";
import { useToast } from "@/components/ui/Toast";
import { useTheme } from "next-themes";
import {
  SettingsIcon,
  SunIcon,
  MoonIcon,
  TagIcon,
  ClockIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
} from "@/components/icons/Icons";
import {
  useGetBranchesQuery,
  useGetBranchSettingsQuery,
  useUpdateBranchSettingsMutation,
  useGetBranchShiftsQuery,
  useCreateBranchShiftMutation,
  useUpdateBranchShiftMutation,
  useDeleteBranchShiftMutation,
  useGetBranchHolidaysQuery,
  useCreateBranchHolidayMutation,
  useUpdateBranchHolidayMutation,
  useDeleteBranchHolidayMutation,
} from "@/lib/api/branchesApi";
import { Skeleton } from "@/components/ui/Skeleton";
import Dropdown from "@/components/ui/Dropdown";
import DataTable from "@/components/ui/DataTable";
import Drawer from "@/components/ui/Drawer";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import { useTimeFormat } from "@/lib/TimeFormatContext";

export function CalendarIcon({ className = "size-5" }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
      <line x1="16" y1="2" x2="16" y2="6" />
      <line x1="8" y1="2" x2="8" y2="6" />
      <line x1="3" y1="10" x2="21" y2="10" />
    </svg>
  );
}

const DAYS_MAP = {
  0: "الأحد",
  1: "الاثنين",
  2: "الثلاثاء",
  3: "الأربعاء",
  4: "الخميس",
  5: "الجمعة",
  6: "السبت",
};

const HOLIDAY_TYPE_MAP = {
  weekly: "عطلة أسبوعية متكررة",
  specific_dates: "إجازة تاريخ محدد",
};

const GENDER_MAP = {
  mixed: "مختلط",
  male: "ذكور",
  female: "إناث",
};

export default function SettingsClient() {
  const toast = useToast();
  const { theme, setTheme } = useTheme();
  const [mounted, setMounted] = useState(false);
  const [activeTab, setActiveTab] = useState("general");
  const [isSaving, setIsSaving] = useState(false);

  const { timeFormat, setTimeFormat, formatTime } = useTimeFormat();

  // States for branch selection
  const [selectedBranchId, setSelectedBranchId] = useState("");

  // RTK Queries & Mutations for Branches & Settings
  const { data: branchesData, isLoading: isLoadingBranches } = useGetBranchesQuery();
  const branches = Array.isArray(branchesData?.data) ? branchesData.data : [];

  const { data: settingsResponse, isFetching: isLoadingSettings } = useGetBranchSettingsQuery(
    selectedBranchId,
    { skip: !selectedBranchId }
  );

  const [updateBranchSettings] = useUpdateBranchSettingsMutation();

  // States for branch settings
  const [defaultClubCommission, setDefaultClubCommission] = useState("40");
  const [defaultCoachCommission, setDefaultCoachCommission] = useState("60");
  const [defaultEmployeeSalary, setDefaultEmployeeSalary] = useState("3500");
  const [workingHoursStart, setWorkingHoursStart] = useState("");
  const [workingHoursEnd, setWorkingHoursEnd] = useState("");

  // States for shifts management
  const { data: shiftsResponse, isFetching: isLoadingShifts } = useGetBranchShiftsQuery(
    selectedBranchId,
    { skip: !selectedBranchId }
  );
  const shifts = Array.isArray(shiftsResponse?.data) ? shiftsResponse.data : [];

  const [createShift, { isLoading: isCreatingShift }] = useCreateBranchShiftMutation();
  const [updateShift, { isLoading: isUpdatingShift }] = useUpdateBranchShiftMutation();
  const [deleteShift, { isLoading: isDeletingShift }] = useDeleteBranchShiftMutation();

  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [drawerMode, setDrawerMode] = useState("create"); // "create" or "edit"
  const [editingShift, setEditingShift] = useState(null);

  const [shiftName, setShiftName] = useState("");
  const [shiftStartTime, setShiftStartTime] = useState("");
  const [shiftEndTime, setShiftEndTime] = useState("");
  const [shiftGender, setShiftGender] = useState("mixed");

  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [deletingShiftId, setDeletingShiftId] = useState(null);

  const filteredShifts = shifts;

  const handlePrint = () => {
    const activeBranch = branches.find((b) => b.id.toString() === selectedBranchId);
    const branchName = activeBranch
      ? (typeof activeBranch.name === "object" ? activeBranch.name.ar || activeBranch.name.en : activeBranch.name)
      : "الفرع المختار";

    const rowsHtml = filteredShifts
      .map(
        (s) => `
      <tr>
        <td>${s.name || "-"}</td>
        <td>${formatTime(s.start_time)}</td>
        <td>${formatTime(s.end_time)}</td>
        <td>${GENDER_MAP[s.gender_allowed] || s.gender_allowed}</td>
      </tr>
    `
      )
      .join("");

    const printWindow = window.open("", "_blank");
    printWindow.document.write(`
      <html>
      <head>
        <title>ورديات العمل - TechnoGYM</title>
        <style>
          @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap');
          body {
            font-family: 'Tajawal', sans-serif;
            direction: rtl;
            text-align: right;
            padding: 40px;
            color: #111;
            background: #fff;
          }
          .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e2b714;
            padding-bottom: 20px;
            margin-bottom: 30px;
          }
          .logo-title {
            font-size: 26px;
            font-weight: bold;
            color: #111;
            letter-spacing: 2px;
            margin: 0;
          }
          .logo-title span {
            color: #e2b714;
          }
          .logo-subtitle {
            font-size: 11px;
            color: #666;
            margin: 5px 0 0 0;
            font-weight: 500;
          }
          .meta-area {
            text-align: left;
            font-size: 13px;
            color: #555;
          }
          .meta-area p {
            margin: 4px 0;
          }
          h1 {
            font-size: 22px;
            margin: 0 0 20px 0;
            color: #111;
            font-weight: 700;
          }
          .branch-badge {
            display: inline-block;
            background: #fdf5d6;
            color: #927008;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 20px;
            border: 1px solid #f6e398;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
          }
          th, td {
            border: 1px solid #e2e8f0;
            padding: 14px;
            text-align: center;
            font-size: 14px;
          }
          th {
            background-color: #f8fafc;
            font-weight: 700;
            color: #334155;
          }
          td {
            color: #475569;
          }
          tr:nth-child(even) td {
            background-color: #f8fafc;
          }
          .footer {
            margin-top: 60px;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
          }
        </style>
      </head>
      <body>
        <div class="header">
          <div>
            <div class="logo-title">TECHNO<span>GYM</span></div>
            <div class="logo-subtitle">نادي تكنولوجي جيم الرياضي</div>
          </div>
          <div class="meta-area">
            <p>تاريخ الطباعة: ${new Date().toLocaleDateString("ar-SY")}</p>
            <p>نظام إدارة الصالات الرياضية</p>
          </div>
        </div>

        <h1>جدول ورديات العمل اليومية</h1>
        <div class="branch-badge">الفرع: ${branchName}</div>

        <table>
          <thead>
            <tr>
              <th>اسم الوردية</th>
              <th>وقت البدء</th>
              <th>وقت الانتهاء</th>
              <th>الفئة المسموح بها</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml || '<tr><td colspan="4">لا توجد ورديات عمل مسجلة</td></tr>'}
          </tbody>
        </table>

        <div class="footer">
          نظام تكنولوجي جيم المتكامل لإدارة الأندية الرياضية © جميع الحقوق محفوظة
        </div>

        <script>
          window.onload = function() {
            setTimeout(function() {
              window.print();
              setTimeout(function() {
                window.close();
              }, 500);
            }, 300);
          };
        </script>
      </body>
      </html>
    `);
    printWindow.document.close();
  };

  // Holidays RTK queries & mutations
  const { data: holidaysResponse, isFetching: isLoadingHolidays } = useGetBranchHolidaysQuery(
    selectedBranchId,
    { skip: !selectedBranchId }
  );
  const holidays = Array.isArray(holidaysResponse?.data) ? holidaysResponse.data : [];

  const [createHoliday, { isLoading: isCreatingHoliday }] = useCreateBranchHolidayMutation();
  const [updateHoliday, { isLoading: isUpdatingHoliday }] = useUpdateBranchHolidayMutation();
  const [deleteHoliday, { isLoading: isDeletingHoliday }] = useDeleteBranchHolidayMutation();

  // Holidays state
  const [isHolidayDrawerOpen, setIsHolidayDrawerOpen] = useState(false);
  const [holidayDrawerMode, setHolidayDrawerMode] = useState("create"); // "create" or "edit"
  const [editingHoliday, setEditingHoliday] = useState(null);

  const [holidayType, setHolidayType] = useState("weekly"); // "weekly" or "specific_dates"
  const [holidayDay, setHolidayDay] = useState("5"); // Friday
  const [holidayStartDate, setHolidayStartDate] = useState("");
  const [holidayEndDate, setHolidayEndDate] = useState("");

  const [deleteHolidayConfirmOpen, setDeleteHolidayConfirmOpen] = useState(false);
  const [deletingHolidayId, setDeletingHolidayId] = useState(null);

  const handleOpenAddHolidayDrawer = () => {
    setHolidayDrawerMode("create");
    setEditingHoliday(null);
    setHolidayType("weekly");
    setHolidayDay("5");
    setHolidayStartDate("");
    setHolidayEndDate("");
    setIsHolidayDrawerOpen(true);
  };

  const handleOpenEditHolidayDrawer = (holiday) => {
    setHolidayDrawerMode("edit");
    setEditingHoliday(holiday);
    setHolidayType(holiday.type || "weekly");
    setHolidayDay(holiday.day_of_week !== null && holiday.day_of_week !== undefined ? holiday.day_of_week.toString() : "5");
    setHolidayStartDate(holiday.start_date || "");
    setHolidayEndDate(holiday.end_date || "");
    setIsHolidayDrawerOpen(true);
  };

  const handleSaveHoliday = async (e) => {
    e.preventDefault();

    if (holidayType === "specific_dates") {
      if (!holidayStartDate || !holidayEndDate) {
        toast.error("يرجى تحديد تاريخ البدء وتاريخ الانتهاء.");
        return;
      }
      if (holidayEndDate < holidayStartDate) {
        toast.error("يجب أن يكون تاريخ الانتهاء بعد تاريخ البدء.");
        return;
      }
    }

    try {
      const body = {
        type: holidayType,
        day_of_week: holidayType === "weekly" ? parseInt(holidayDay) : null,
        start_date: holidayType === "specific_dates" ? holidayStartDate : null,
        end_date: holidayType === "specific_dates" ? holidayEndDate : null,
      };

      if (holidayDrawerMode === "create") {
        await createHoliday({ branchId: selectedBranchId, body }).unwrap();
        toast.success("تمت إضافة الإجازة بنجاح");
      } else {
        await updateHoliday({
          branchId: selectedBranchId,
          holidayId: editingHoliday.id,
          body,
        }).unwrap();
        toast.success("تم تعديل الإجازة بنجاح");
      }
      setIsHolidayDrawerOpen(false);
    } catch (err) {
      console.error(err);
      const errMsg = err?.data?.message || err?.data?.error || "حدث خطأ أثناء حفظ الإجازة";
      toast.error(errMsg);
    }
  };

  const handleDeleteHolidayClick = (id) => {
    setDeletingHolidayId(id);
    setDeleteHolidayConfirmOpen(true);
  };

  const handleConfirmDeleteHoliday = async () => {
    try {
      await deleteHoliday({
        branchId: selectedBranchId,
        holidayId: deletingHolidayId,
      }).unwrap();
      toast.success("تم حذف الإجازة بنجاح");
      setDeleteHolidayConfirmOpen(false);
    } catch (err) {
      console.error(err);
      toast.error("حدث خطأ أثناء حذف الإجازة");
    }
  };

  // Load mount state
  useEffect(() => {
    setMounted(true);
  }, []);

  // Set default selected branch
  useEffect(() => {
    if (branches.length > 0 && !selectedBranchId) {
      setSelectedBranchId(branches[0].id.toString());
    }
  }, [branches, selectedBranchId]);

  // Populate branch settings fields
  useEffect(() => {
    if (settingsResponse?.data) {
      const s = settingsResponse.data;
      setDefaultClubCommission(s.default_club_commission_percentage || "0");
      setDefaultCoachCommission(s.default_coach_commission_percentage || "0");
      setDefaultEmployeeSalary(s.default_employee_salary || "0");
      setWorkingHoursStart(s.working_hours_start ? s.working_hours_start.slice(0, 5) : "");
      setWorkingHoursEnd(s.working_hours_end ? s.working_hours_end.slice(0, 5) : "");
    }
  }, [settingsResponse]);

  const formatTimeForApi = (timeStr) => {
    if (!timeStr) return null;
    return timeStr.slice(0, 5);
  };

  const handleSaveSettings = async (e) => {
    e.preventDefault();
    setIsSaving(true);

    // Client-side validation for working hours
    if (workingHoursStart && workingHoursEnd) {
      if (workingHoursEnd <= workingHoursStart) {
        toast.error("يجب أن يكون وقت نهاية ساعات العمل بعد وقت البداية.");
        setIsSaving(false);
        return;
      }
    } else if (workingHoursStart && !workingHoursEnd) {
      toast.error("يرجى تحديد وقت نهاية ساعات العمل.");
      setIsSaving(false);
      return;
    } else if (!workingHoursStart && workingHoursEnd) {
      toast.error("يرجى تحديد وقت بداية ساعات العمل.");
      setIsSaving(false);
      return;
    }

    try {
      if (selectedBranchId) {
        await updateBranchSettings({
          branchId: selectedBranchId,
          body: {
            default_club_commission_percentage: parseFloat(defaultClubCommission) || 0,
            default_coach_commission_percentage: parseFloat(defaultCoachCommission) || 0,
            default_employee_salary: parseFloat(defaultEmployeeSalary) || 0,
            working_hours_start: formatTimeForApi(workingHoursStart),
            working_hours_end: formatTimeForApi(workingHoursEnd),
          },
        }).unwrap();
      }

      toast.success("تم حفظ الإعدادات بنجاح");
    } catch (err) {
      console.error("Detailed save error:", {
        status: err?.status,
        data: err?.data,
        error: err
      });
      let errorMsg = "حدث خطأ أثناء حفظ إعدادات الفرع.";
      if (err?.data?.errors) {
        const validationMsgs = Object.values(err.data.errors).flat().join(" | ");
        errorMsg = `${err.data.message || ""}: ${validationMsgs}`;
      } else if (err?.data?.message) {
        errorMsg = err.data.message;
      } else if (err?.message) {
        errorMsg = err.message;
      }
      toast.error(errorMsg);
    } finally {
      setIsSaving(false);
    }
  };

  // Shifts Actions handlers
  const handleOpenAddDrawer = () => {
    setDrawerMode("create");
    setShiftName("");
    setShiftStartTime("08:00");
    setShiftEndTime("16:00");
    setShiftGender("mixed");
    setEditingShift(null);
    setIsDrawerOpen(true);
  };

  const handleOpenEditDrawer = (shift) => {
    setDrawerMode("edit");
    setEditingShift(shift);
    setShiftName(shift.name || "");
    setShiftStartTime(shift.start_time ? shift.start_time.slice(0, 5) : "");
    setShiftEndTime(shift.end_time ? shift.end_time.slice(0, 5) : "");
    setShiftGender(shift.gender_allowed || "mixed");
    setIsDrawerOpen(true);
  };

  const handleSaveShift = async (e) => {
    e.preventDefault();

    if (!shiftStartTime || !shiftEndTime) {
      toast.error("يرجى تحديد وقت البدء ووقت الانتهاء.");
      return;
    }
    if (shiftEndTime <= shiftStartTime) {
      toast.error("يجب أن يكون وقت الانتهاء بعد وقت البدء.");
      return;
    }

    try {
      const body = {
        name: shiftName,
        day_of_week: 0,
        start_time: formatTimeForApi(shiftStartTime),
        end_time: formatTimeForApi(shiftEndTime),
        gender_allowed: shiftGender,
      };

      if (drawerMode === "create") {
        await createShift({ branchId: selectedBranchId, body }).unwrap();
        toast.success("تمت إضافة الوردية بنجاح");
      } else {
        await updateShift({
          branchId: selectedBranchId,
          shiftId: editingShift.id,
          body,
        }).unwrap();
        toast.success("تم تعديل الوردية بنجاح");
      }
      setIsDrawerOpen(false);
    } catch (err) {
      console.error("Error saving shift:", err);
      let errorMsg = "حدث خطأ أثناء حفظ الوردية.";
      if (err?.data?.errors) {
        errorMsg = Object.values(err.data.errors).flat().join(" | ");
      } else if (err?.data?.message) {
        errorMsg = err.data.message;
      }
      toast.error(errorMsg);
    }
  };

  const handleDeleteClick = (shiftId) => {
    setDeletingShiftId(shiftId);
    setDeleteConfirmOpen(true);
  };

  const handleConfirmDelete = async () => {
    try {
      await deleteShift({
        branchId: selectedBranchId,
        shiftId: deletingShiftId,
      }).unwrap();
      toast.success("تم حذف الوردية بنجاح");
      setDeleteConfirmOpen(false);
    } catch (err) {
      console.error("Error deleting shift:", err);
      let errorMsg = "حدث خطأ أثناء حذف الوردية.";
      if (err?.data?.message) {
        errorMsg = err.data.message;
      }
      toast.error(errorMsg);
    }
  };

  if (!mounted) {
    return null;
  }

  return (
    <div className="mx-auto max-w-5xl space-y-8 animate-fade-in" dir="rtl">
      <PageHeader
        eyebrow="لوحة التحكم"
        title="إعدادات النظام"
        subtitle="تعديل وضبط الإعدادات العامة للنادي والخيارات المفضلة للنظام والورديات."
      />

      <div className="grid gap-6 md:grid-cols-[220px_1fr]">
        {/* Navigation Tabs */}
        <div className="flex flex-col gap-2 rounded-2xl border border-app-line bg-app-panel p-3 h-fit">
          <button
            onClick={() => setActiveTab("general")}
            className={`flex h-11 items-center gap-3 rounded-lg px-4 text-sm font-medium transition text-right ${
              activeTab === "general"
                ? "bg-app-yellow-soft text-app-yellow"
                : "text-app-muted-light hover:bg-app-line-soft hover:text-app-text"
            }`}
          >
            <SettingsIcon className="size-5 shrink-0" />
            <span>الإعدادات العامة</span>
          </button>

          <button
            onClick={() => setActiveTab("appearance")}
            className={`flex h-11 items-center gap-3 rounded-lg px-4 text-sm font-medium transition text-right ${
              activeTab === "appearance"
                ? "bg-app-yellow-soft text-app-yellow"
                : "text-app-muted-light hover:bg-app-line-soft hover:text-app-text"
            }`}
          >
            {theme === "dark" ? (
              <MoonIcon className="size-5 shrink-0" />
            ) : (
              <SunIcon className="size-5 shrink-0" />
            )}
            <span>المظهر والنظام</span>
          </button>

          <button
            onClick={() => setActiveTab("shifts")}
            className={`flex h-11 items-center gap-3 rounded-lg px-4 text-sm font-medium transition text-right ${
              activeTab === "shifts"
                ? "bg-app-yellow-soft text-app-yellow"
                : "text-app-muted-light hover:bg-app-line-soft hover:text-app-text"
            }`}
          >
            <ClockIcon className="size-5 shrink-0" />
            <span>ورديات الفروع</span>
          </button>

          <button
            onClick={() => setActiveTab("holidays")}
            className={`flex h-11 items-center gap-3 rounded-lg px-4 text-sm font-medium transition text-right ${
              activeTab === "holidays"
                ? "bg-app-yellow-soft text-app-yellow"
                : "text-app-muted-light hover:bg-app-line-soft hover:text-app-text"
            }`}
          >
            <CalendarIcon className="size-5 shrink-0" />
            <span>إجازات الفروع</span>
          </button>
        </div>

        {/* Content Area */}
        <div className="rounded-2xl border border-app-line bg-app-panel p-6 shadow-sm min-w-0">
          <form onSubmit={handleSaveSettings} className="space-y-6">
            {activeTab === "general" && (
              <div className="space-y-6">
                <div>
                  <h3 className="text-lg font-medium text-app-text text-right">
                    إعدادات فروع النادي
                  </h3>
                  <p className="text-sm text-app-muted-light text-right mt-1">
                    ضبط الإعدادات المالية وأوقات العمل لكل فرع من فروع النادي.
                  </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                  <label className="block text-right">
                    <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
                      <TagIcon className="size-4 shrink-0 text-app-yellow" />
                      <span>اختر الفرع لتعديل إعداداته</span>
                      <span className="text-app-red">*</span>
                    </span>
                    <Dropdown
                      options={branches.map((b) => ({
                        value: b.id.toString(),
                        label: typeof b.name === "object" ? b.name?.ar || b.name?.en : b.name
                      }))}
                      value={selectedBranchId}
                      onChange={(val) => setSelectedBranchId(val)}
                      placeholder="اختر الفرع"
                      buttonClassName="h-[46px]"
                      disabled={isLoadingBranches || branches.length === 0}
                    />
                  </label>
                </div>

                {selectedBranchId && (
                  <>
                    {isLoadingSettings ? (
                      <div className="grid gap-4 sm:grid-cols-2">
                        <Skeleton className="h-11 w-full" />
                        <Skeleton className="h-11 w-full" />
                        <Skeleton className="h-11 w-full text-right" />
                        <Skeleton className="h-[46px] w-full" />
                        <Skeleton className="h-[46px] w-full" />
                      </div>
                    ) : (
                      <>
                        <div className="grid gap-4 sm:grid-cols-2">
                          <Field
                            label="نسبة عمولة النادي الافتراضية (%)"
                            type="number"
                            required
                            min="0"
                            max="100"
                            step="0.01"
                            value={defaultClubCommission}
                            onChange={(e) => setDefaultClubCommission(e.target.value)}
                            placeholder="40"
                          />
                          <Field
                            label="نسبة عمولة المدرب الافتراضية (%)"
                            type="number"
                            required
                            min="0"
                            max="100"
                            step="0.01"
                            value={defaultCoachCommission}
                            onChange={(e) => setDefaultCoachCommission(e.target.value)}
                            placeholder="60"
                          />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                          <Field
                            label="راتب الموظف الافتراضي"
                            type="number"
                            required
                            min="0"
                            step="0.01"
                            value={defaultEmployeeSalary}
                            onChange={(e) => setDefaultEmployeeSalary(e.target.value)}
                            placeholder="3500"
                          />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                          <Field
                            label="بداية ساعات العمل للفرع"
                            type="time"
                            required={false}
                            value={workingHoursStart}
                            onChange={(val) => setWorkingHoursStart(val)}
                          />
                          <Field
                            label="نهاية ساعات العمل للفرع"
                            type="time"
                            required={false}
                            value={workingHoursEnd}
                            onChange={(val) => setWorkingHoursEnd(val)}
                          />
                        </div>
                      </>
                    )}
                  </>
                )}
              </div>
            )}

            {activeTab === "appearance" && (
              <div className="space-y-6">
                <div>
                  <h3 className="text-lg font-medium text-app-text text-right">
                    مظهر النظام والواجهة
                  </h3>
                  <p className="text-sm text-app-muted-light text-right mt-1">
                    تخصيص الواجهة واختيار سمة الألوان المفضلة لديك.
                  </p>
                </div>

                <div className="space-y-4">
                  <label className="block text-right">
                    <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
                      <TagIcon className="size-4 shrink-0 text-app-yellow" />
                      <span>السمة والمظهر العام</span>
                    </span>
                    <div className="grid grid-cols-2 gap-4">
                      <button
                        type="button"
                        onClick={() => setTheme("light")}
                        className={`flex flex-col items-center justify-center gap-3 rounded-xl border p-4 transition ${
                          theme === "light"
                            ? "border-app-yellow bg-app-yellow-soft/10 text-app-yellow"
                            : "border-app-line bg-app-panel-soft text-app-muted hover:text-app-text"
                        }`}
                      >
                        <SunIcon className="size-6" />
                        <span className="text-sm font-medium">الوضع المضيء</span>
                      </button>

                      <button
                        type="button"
                        onClick={() => setTheme("dark")}
                        className={`flex flex-col items-center justify-center gap-3 rounded-xl border p-4 transition ${
                          theme === "dark"
                            ? "border-app-yellow bg-app-yellow-soft/10 text-app-yellow"
                            : "border-app-line bg-app-panel-soft text-app-muted hover:text-app-text"
                        }`}
                      >
                        <MoonIcon className="size-6" />
                        <span className="text-sm font-medium">الوضع الداكن</span>
                      </button>
                    </div>
                  </label>

                  <label className="block text-right">
                    <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
                      <TagIcon className="size-4 shrink-0 text-app-yellow" />
                      <span>نظام عرض التوقيت</span>
                      <span className="text-app-red">*</span>
                    </span>
                    <Dropdown
                      options={[
                        { value: "12", label: "نظام 12 ساعة (ص/م)" },
                        { value: "24", label: "نظام 24 ساعة" }
                      ]}
                      value={timeFormat}
                      onChange={(val) => setTimeFormat(val)}
                      placeholder="اختر نظام عرض الوقت"
                      buttonClassName="h-[46px]"
                    />
                  </label>
                </div>
              </div>
            )}

            {activeTab === "shifts" && (
              <div className="space-y-6 animate-fade-in">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-app-line pb-4">
                  <div>
                    <h3 className="text-lg font-medium text-app-text text-right">
                      ورديات عمل الفرع
                    </h3>
                    <p className="text-sm text-app-muted-light text-right mt-1">
                      عرض وإدارة ورديات العمل اليومية وتحديد فترات العمل والفئة المستهدفة.
                    </p>
                  </div>
                  {selectedBranchId && (
                    <div className="flex flex-wrap items-center gap-3">
                      {/* Print Button */}
                      <Button
                        type="button"
                        tone="outline"
                        onClick={handlePrint}
                        className="h-10 text-xs flex items-center gap-2"
                      >
                        <svg
                          className="size-4"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                          strokeWidth="2"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                          />
                        </svg>
                        <span>طباعة</span>
                      </Button>

                      {/* Add Shift Button */}
                      <Button
                        type="button"
                        tone="primary"
                        onClick={handleOpenAddDrawer}
                        className="h-10 text-xs flex items-center gap-2"
                      >
                        <PlusIcon className="size-4 shrink-0" />
                        <span>إضافة وردية</span>
                      </Button>
                    </div>
                  )}
                </div>

                <div className="grid gap-4">
                  <label className="block text-right">
                    <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
                      <TagIcon className="size-4 shrink-0 text-app-yellow" />
                      <span>اختر الفرع لعرض وردياته</span>
                      <span className="text-app-red">*</span>
                    </span>
                    <Dropdown
                      options={branches.map((b) => ({
                        value: b.id.toString(),
                        label: typeof b.name === "object" ? b.name?.ar || b.name?.en : b.name
                      }))}
                      value={selectedBranchId}
                      onChange={(val) => setSelectedBranchId(val)}
                      placeholder="اختر الفرع"
                      buttonClassName="h-[46px]"
                      disabled={isLoadingBranches || branches.length === 0}
                    />
                  </label>
                </div>

                {selectedBranchId && (
                  <DataTable
                    columns={[
                      {
                        key: "name",
                        label: "اسم الوردية",
                        align: "right",
                        width: "150px",
                        render: (val) => val || "-",
                      },
                      {
                        key: "start_time",
                        label: "وقت البدء",
                        align: "center",
                        width: "120px",
                        render: (val) => formatTime(val),
                      },
                      {
                        key: "end_time",
                        label: "وقت الانتهاء",
                        align: "center",
                        width: "120px",
                        render: (val) => formatTime(val),
                      },
                      {
                        key: "gender_allowed",
                        label: "الفئة المسموح بها",
                        align: "center",
                        width: "140px",
                        render: (val) => GENDER_MAP[val] ?? val,
                      },
                      {
                        key: "actions",
                        label: "الإجراءات",
                        align: "center",
                        width: "140px",
                        render: (_, row) => (
                          <div className="flex items-center justify-center gap-2">
                            <button
                              type="button"
                              onClick={() => handleOpenEditDrawer(row)}
                              className="grid size-8 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-muted-light hover:border-app-yellow/60 hover:text-app-yellow transition"
                              title="تعديل"
                            >
                              <PencilIcon className="size-4" />
                            </button>
                            <button
                              type="button"
                              onClick={() => handleDeleteClick(row.id)}
                              className="grid size-8 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-muted-light hover:border-app-red/60 hover:text-app-red transition"
                              title="حذف"
                            >
                              <TrashIcon className="size-4" />
                            </button>
                          </div>
                        ),
                      },
                    ]}
                    rows={filteredShifts}
                    isLoading={isLoadingShifts}
                    minWidth="600px"
                    showToolbar={false}
                  />
                )}
              </div>
            )}

            {activeTab === "holidays" && (
              <div className="space-y-6 animate-fade-in">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-app-line pb-4">
                  <div>
                    <h3 className="text-lg font-medium text-app-text text-right">
                      عطلات وإجازات الفرع
                    </h3>
                    <p className="text-sm text-app-muted-light text-right mt-1">
                      ضبط الإجازات الرسمية والعطلات الأسبوعية للفرع المحدد.
                    </p>
                  </div>
                  {selectedBranchId && (
                    <Button
                      type="button"
                      tone="primary"
                      onClick={handleOpenAddHolidayDrawer}
                      className="flex items-center gap-2 self-start sm:self-center h-10 text-xs"
                    >
                      <PlusIcon className="size-4 shrink-0" />
                      <span>إضافة إجازة</span>
                    </Button>
                  )}
                </div>

                <div className="grid gap-4">
                  <label className="block text-right">
                    <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
                      <TagIcon className="size-4 shrink-0 text-app-yellow" />
                      <span>اختر الفرع لعرض إجازاته</span>
                      <span className="text-app-red">*</span>
                    </span>
                    <Dropdown
                      options={branches.map((b) => ({
                        value: b.id.toString(),
                        label: typeof b.name === "object" ? b.name?.ar || b.name?.en : b.name
                      }))}
                      value={selectedBranchId}
                      onChange={(val) => setSelectedBranchId(val)}
                      placeholder="اختر الفرع"
                      buttonClassName="h-[46px]"
                      disabled={isLoadingBranches || branches.length === 0}
                    />
                  </label>
                </div>

                {selectedBranchId && (
                  <DataTable
                    columns={[
                      {
                        key: "type",
                        label: "نوع العطلة / الإجازة",
                        align: "center",
                        width: "180px",
                        render: (val) => HOLIDAY_TYPE_MAP[val] ?? val,
                      },
                      {
                        key: "details",
                        label: "تفاصيل الفترة",
                        align: "center",
                        width: "240px",
                        render: (_, row) => {
                          if (row.type === "weekly") {
                            return `كل يوم ${DAYS_MAP[row.day_of_week] || row.day_of_week}`;
                          }
                          const start = row.start_date ? new Date(row.start_date).toLocaleDateString("ar-SY") : "";
                          const end = row.end_date ? new Date(row.end_date).toLocaleDateString("ar-SY") : "";
                          return `من ${start} إلى ${end}`;
                        },
                      },
                      {
                        key: "actions",
                        label: "الإجراءات",
                        align: "center",
                        width: "140px",
                        render: (_, row) => (
                          <div className="flex items-center justify-center gap-2">
                            <button
                              type="button"
                              onClick={() => handleOpenEditHolidayDrawer(row)}
                              className="grid size-8 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-muted-light hover:border-app-yellow/60 hover:text-app-yellow transition"
                              title="تعديل"
                            >
                              <PencilIcon className="size-4" />
                            </button>
                            <button
                              type="button"
                              onClick={() => handleDeleteHolidayClick(row.id)}
                              className="grid size-8 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-muted-light hover:border-app-red/60 hover:text-app-red transition"
                              title="حذف"
                            >
                              <TrashIcon className="size-4" />
                            </button>
                          </div>
                        ),
                      },
                    ]}
                    rows={holidays}
                    isLoading={isLoadingHolidays}
                    minWidth="600px"
                    showToolbar={false}
                  />
                )}
              </div>
            )}

            {/* Footer Form Actions */}
            {activeTab !== "shifts" && activeTab !== "holidays" && (
              <div className="flex items-center justify-end gap-3 border-t border-app-line pt-5 mt-8">
                <Button
                  type="submit"
                  tone="primary"
                  loading={isSaving}
                  loadingLabel="جاري الحفظ"
                  className="px-8"
                >
                  حفظ التغييرات
                </Button>
              </div>
            )}
          </form>
        </div>
      </div>

      {/* Shifts Drawer */}
      <Drawer
        open={isDrawerOpen}
        onClose={() => setIsDrawerOpen(false)}
        title={drawerMode === "create" ? "إضافة وردية جديدة" : "تعديل الوردية"}
        subtitle="حدد خيارات الوردية اليومية للفرع."
        footer={
          <div className="flex items-center gap-3 justify-end">
            <Button
              type="button"
              tone="outline"
              onClick={() => setIsDrawerOpen(false)}
              className="px-6"
            >
              إلغاء
            </Button>
            <Button
              type="button"
              tone="primary"
              onClick={handleSaveShift}
              loading={isCreatingShift || isUpdatingShift}
              className="px-6"
            >
              حفظ
            </Button>
          </div>
        }
      >
        <div className="space-y-4">
          <Field
            label="اسم الوردية"
            type="text"
            required={false}
            value={shiftName}
            onChange={(e) => setShiftName(e.target.value)}
            placeholder="مثال: وردية الصباح"
          />



          <Field
            label="وقت البدء"
            type="time"
            required
            value={shiftStartTime}
            onChange={(val) => setShiftStartTime(val)}
          />

          <Field
            label="وقت الانتهاء"
            type="time"
            required
            value={shiftEndTime}
            onChange={(val) => setShiftEndTime(val)}
          />

          <label className="block text-right">
            <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
              <TagIcon className="size-4 shrink-0 text-app-yellow" />
              <span>الفئة المسموح بها</span>
            </span>
            <Dropdown
              options={[
                { value: "mixed", label: "مختلط" },
                { value: "male", label: "ذكور" },
                { value: "female", label: "إناث" },
              ]}
              value={shiftGender}
              onChange={(val) => setShiftGender(val)}
              placeholder="اختر الفئة"
              buttonClassName="h-[46px]"
            />
          </label>
        </div>
      </Drawer>

      {/* Delete Confirmation Dialog */}
      <ConfirmDialog
        open={deleteConfirmOpen}
        onClose={() => setDeleteConfirmOpen(false)}
        onConfirm={handleConfirmDelete}
        isLoading={isDeletingShift}
        title="حذف الوردية"
        message="هل أنت متأكد من رغبتك في حذف هذه الوردية؟ لا يمكن التراجع عن هذا الإجراء."
      />

      {/* Holidays Drawer */}
      <Drawer
        open={isHolidayDrawerOpen}
        onClose={() => setIsHolidayDrawerOpen(false)}
        title={holidayDrawerMode === "create" ? "إضافة عطلة / إجازة" : "تعديل عطلة / إجازة"}
        subtitle="حدد نوع وتاريخ العطلة أو الإجازة الرسمية للفرع."
        footer={
          <div className="flex items-center gap-3 justify-end">
            <Button
              type="button"
              tone="outline"
              onClick={() => setIsHolidayDrawerOpen(false)}
              className="px-6"
            >
              إلغاء
            </Button>
            <Button
              type="button"
              tone="primary"
              onClick={handleSaveHoliday}
              loading={isCreatingHoliday || isUpdatingHoliday}
              className="px-6"
            >
              حفظ
            </Button>
          </div>
        }
      >
        <div className="space-y-4">
          <label className="block text-right">
            <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
              <TagIcon className="size-4 shrink-0 text-app-yellow" />
              <span>نوع العطلة / الإجازة</span>
            </span>
            <Dropdown
              options={[
                { value: "weekly", label: "عطلة أسبوعية متكررة" },
                { value: "specific_dates", label: "إجازة محددة بالتاريخ" },
              ]}
              value={holidayType}
              onChange={(val) => setHolidayType(val)}
              placeholder="اختر النوع"
              buttonClassName="h-[46px]"
            />
          </label>

          {holidayType === "weekly" ? (
            <label className="block text-right">
              <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
                <TagIcon className="size-4 shrink-0 text-app-yellow" />
                <span>اليوم</span>
              </span>
              <Dropdown
                options={[
                  { value: "0", label: "الأحد" },
                  { value: "1", label: "الاثنين" },
                  { value: "2", label: "الثلاثاء" },
                  { value: "3", label: "الأربعاء" },
                  { value: "4", label: "الخميس" },
                  { value: "5", label: "الجمعة" },
                  { value: "6", label: "السبت" },
                ]}
                value={holidayDay}
                onChange={(val) => setHolidayDay(val)}
                placeholder="اختر اليوم"
                buttonClassName="h-[46px]"
              />
            </label>
          ) : (
            <>
              <Field
                label="تاريخ البدء"
                type="date"
                required
                value={holidayStartDate}
                onChange={(val) => setHolidayStartDate(val)}
              />
              <Field
                label="تاريخ الانتهاء"
                type="date"
                required
                value={holidayEndDate}
                onChange={(val) => setHolidayEndDate(val)}
              />
            </>
          )}
        </div>
      </Drawer>

      {/* Delete Holiday Confirmation Dialog */}
      <ConfirmDialog
        open={deleteHolidayConfirmOpen}
        onClose={() => setDeleteHolidayConfirmOpen(false)}
        onConfirm={handleConfirmDeleteHoliday}
        isLoading={isDeletingHoliday}
        title="حذف العطلة / الإجازة"
        message="هل أنت متأكد من رغبتك في حذف هذه العطلة/إيجازة؟ لا يمكن التراجع عن هذا الإجراء."
      />
    </div>
  );
}
