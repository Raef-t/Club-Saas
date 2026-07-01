import { SearchIcon } from "@/components/icons/Icons";

export default function SearchInput({
  value,
  onChange,
  placeholder = "بحث...",
  className = "",
}) {
  return (
    <label className={`relative block min-w-64 ${className}`}>
      <SearchIcon className="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
      <input
        className="app-input h-10 w-full pr-9 pl-3 text-sm outline-none transition focus:border-app-yellow/70 bg-app-card-soft text-white"
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        type="search"
      />
    </label>
  );
}
