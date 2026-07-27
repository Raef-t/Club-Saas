import Dropdown from "@/components/ui/Dropdown";
import { TagIcon } from "@/components/icons/Icons";
import { getBranchName } from "@/app/management/settings/settingsUtils";

/**
 * Renders the branch selector shared by settings, shifts, and holidays.
 */
export default function BranchSelector({ branches, value, onChange, isLoading, label, error }) {
  return (
    <label className="block text-right">
      <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
        <TagIcon className="size-4 shrink-0 text-app-yellow" />
        <span>{label}</span>
        <span className="text-app-red">*</span>
      </span>
      <Dropdown
        options={branches.map((branch) => ({
          value: String(branch.id),
          label: getBranchName(branch),
        }))}
        value={value}
        onChange={onChange}
        placeholder="اختر الفرع"
        buttonClassName={`h-[46px] ${error ? "border-app-red bg-app-red/5" : ""}`}
        disabled={isLoading || branches.length === 0}
      />
      {error && (
        <span className="mt-1.5 block text-xs text-app-red" role="alert">
          {error}
        </span>
      )}
    </label>
  );
}
