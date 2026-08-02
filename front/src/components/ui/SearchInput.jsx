import { SearchIcon } from "@/components/icons/Icons";

export default function SearchInput({
  value,
  onChange,
  placeholder = "بحث...",
  className = "",
}) {
  return (
    <label className={`relative block w-full sm:min-w-72 md:min-w-80 ${className}`}>
      <SearchIcon className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
      <input
        className="app-input h-10 w-full bg-app-card-soft ps-9 pe-3 text-right text-sm text-app-text outline-none transition placeholder-app-muted focus:border-app-yellow/70"
        dir="rtl"
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        type="search"
      />
    </label>
  );
}
