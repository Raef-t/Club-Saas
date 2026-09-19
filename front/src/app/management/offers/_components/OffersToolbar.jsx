import Dropdown from "@/components/ui/Dropdown";
import SearchInput from "@/components/ui/SearchInput";
import { FilterIcon } from "@/components/icons/Icons";

export const OFFER_STATUS_FILTER_OPTIONS = [
  { value: "all", label: "جميع الحالات" },
  { value: "available", label: "المتاحة للاشتراك فقط" },
  { value: "inactive", label: "المعطلة أو المكتملة" },
];

export default function OffersToolbar({
  searchTerm,
  onSearchChange,
  statusFilter,
  onStatusFilterChange,
}) {
  return (
    <div className="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
      <SearchInput
        value={searchTerm}
        onChange={(event) => onSearchChange(event.target.value)}
        placeholder="بحث باسم العرض أو الفعالية..."
        className="w-full sm:w-80 md:w-96"
      />
      <Dropdown
        ariaLabel="تصفية العروض حسب الحالة"
        className="w-full text-app-text sm:w-56"
        buttonClassName="h-10 border border-app-line bg-app-card-soft"
        icon={FilterIcon}
        value={statusFilter}
        options={OFFER_STATUS_FILTER_OPTIONS}
        onChange={onStatusFilterChange}
      />
    </div>
  );
}
