"use client";

import { useState } from "react";
import { useTheme } from "next-themes";
import PageHeader from "@/components/common/PageHeader";
import { useTimeFormat } from "@/lib/TimeFormatContext";
import AppearanceSettingsTab from "./AppearanceSettingsTab";
import GeneralSettingsTab from "./GeneralSettingsTab";
import HolidaysSettingsTab from "./HolidaysSettingsTab";
import SettingsLoadError from "./SettingsLoadError";
import SettingsNavigation from "./SettingsNavigation";
import ShiftsSettingsTab from "./ShiftsSettingsTab";
import { useBranchHolidays } from "./useBranchHolidays";
import { useBranchSettings } from "./useBranchSettings";
import { useBranchShifts } from "./useBranchShifts";
import { useSettingsBranches } from "./useSettingsBranches";

/**
 * Composes all settings sections while delegating their state to focused hooks.
 */
export default function SettingsClient({ initialData }) {
  const [activeTab, setActiveTab] = useState("general");
  const { theme, setTheme } = useTheme();
  const { timeFormat, setTimeFormat, formatTime } = useTimeFormat();
  const branchState = useSettingsBranches(initialData?.branches);
  const initialBranchId =
    initialData?.selectedBranchId == null ? "" : String(initialData.selectedBranchId);
  const settingsState = useBranchSettings({
    branchId: branchState.selectedBranchId,
    initialBranchId,
    initialSettings: initialData?.settings,
  });
  const shiftsState = useBranchShifts({
    branchId: branchState.selectedBranchId,
    branches: branchState.branches,
    formatTime,
    initialBranchId,
    initialShifts: initialData?.shifts,
  });
  const holidaysState = useBranchHolidays({
    branchId: branchState.selectedBranchId,
    initialBranchId,
    initialHolidays: initialData?.holidays,
  });

  return (
    <div className="mx-auto max-w-5xl space-y-8 animate-fade-in" dir="rtl">
      <PageHeader
        eyebrow="لوحة التحكم"
        title="إعدادات النظام"
        subtitle="تعديل وضبط الإعدادات العامة للنادي والخيارات المفضلة للنظام والورديات."
      />

      <SettingsLoadError
        message={branchState.errorMessage}
        onRetry={branchState.retry}
        isRetrying={branchState.isFetching}
      />

      <div className="grid gap-6 md:grid-cols-[220px_1fr]">
        <SettingsNavigation activeTab={activeTab} onChange={setActiveTab} theme={theme} />

        <main className="min-w-0 rounded-2xl border border-app-line bg-app-panel p-6 shadow-sm">
          {activeTab === "general" && (
            <GeneralSettingsTab
              branches={branchState.branches}
              selectedBranchId={branchState.selectedBranchId}
              onBranchChange={branchState.setSelectedBranchId}
              isLoadingBranches={branchState.isLoading}
              settings={settingsState}
            />
          )}

          {activeTab === "appearance" && (
            <AppearanceSettingsTab
              theme={theme}
              onThemeChange={setTheme}
              timeFormat={timeFormat}
              onTimeFormatChange={setTimeFormat}
            />
          )}

          {activeTab === "shifts" && (
            <ShiftsSettingsTab
              branches={branchState.branches}
              selectedBranchId={branchState.selectedBranchId}
              onBranchChange={branchState.setSelectedBranchId}
              isLoadingBranches={branchState.isLoading}
              formatTime={formatTime}
              state={shiftsState}
            />
          )}

          {activeTab === "holidays" && (
            <HolidaysSettingsTab
              branches={branchState.branches}
              selectedBranchId={branchState.selectedBranchId}
              onBranchChange={branchState.setSelectedBranchId}
              isLoadingBranches={branchState.isLoading}
              state={holidaysState}
            />
          )}
        </main>
      </div>
    </div>
  );
}
