"use client";

import { useMemo, useState } from "react";
import Button from "@/components/ui/Button";
import Checkbox from "@/components/ui/Checkbox";
import SearchInput from "@/components/ui/SearchInput";
import { ChevronDownIcon, CheckCircleIcon } from "@/components/icons/Icons";
import { getPermissionLabel, groupPermissions } from "../roleUtils";

function createSignature(names) {
  return Array.from(names).sort().join("|");
}

export default function RolePermissionsAccordion({
  permissions,
  selectedPermissionNames,
  onSave,
  isSaving,
  readOnly = false,
  title = "صلاحيات الدور",
  description = "افتح كل قسم وحدد العمليات التي يسمح لهذا الدور بتنفيذها.",
}) {
  const initialGroups = useMemo(() => groupPermissions(permissions), [permissions]);
  const [selected, setSelected] = useState(() => new Set(selectedPermissionNames));
  const [baseline, setBaseline] = useState(() => new Set(selectedPermissionNames));
  const [search, setSearch] = useState("");
  const [openModules, setOpenModules] = useState(
    () => new Set(initialGroups.slice(0, 1).map((group) => group.module)),
  );
  const groups = useMemo(() => groupPermissions(permissions, search), [permissions, search]);
  const isDirty = createSignature(selected) !== createSignature(baseline);

  function togglePermission(name) {
    setSelected((current) => {
      const next = new Set(current);
      if (next.has(name)) next.delete(name);
      else next.add(name);
      return next;
    });
  }

  function toggleGroup(items) {
    const names = items.map((permission) => permission.name);
    const allSelected = names.every((name) => selected.has(name));
    setSelected((current) => {
      const next = new Set(current);
      names.forEach((name) => (allSelected ? next.delete(name) : next.add(name)));
      return next;
    });
  }

  function toggleModule(permissionModule) {
    setOpenModules((current) => {
      const next = new Set(current);
      if (next.has(permissionModule)) next.delete(permissionModule);
      else next.add(permissionModule);
      return next;
    });
  }

  function toggleAll() {
    setSelected((current) =>
      current.size === permissions.length
        ? new Set()
        : new Set(permissions.map((permission) => permission.name)),
    );
  }

  async function handleSave() {
    const names = Array.from(selected).sort();
    const saved = await onSave(names);
    if (saved) setBaseline(new Set(names));
  }

  return (
    <section className="card-shell overflow-hidden rounded-2xl">
      <div className="border-b border-app-line px-5 py-4">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div className="min-w-0 text-right">
            <h2 className="text-base font-semibold text-app-text">{title}</h2>
            <p className="mt-1 text-xs leading-5 text-app-muted-light">{description}</p>
          </div>
          <div className="flex flex-col gap-2.5 sm:flex-row sm:items-center">
            <SearchInput
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="ابحث في الصلاحيات..."
              className="w-full sm:w-64 md:w-72 sm:min-w-0 md:min-w-0"
            />
            <Button
              type="button"
              tone="outline"
              onClick={toggleAll}
              disabled={readOnly}
              className="h-10 shrink-0 whitespace-nowrap px-4 text-xs font-medium"
            >
              {selected.size === permissions.length ? "إلغاء تحديد الكل" : "تحديد الكل"}
            </Button>
          </div>
        </div>
      </div>

      <div className="space-y-3 p-4 sm:p-5">
        {groups.map((group) => {
          const forcedOpen = Boolean(search.trim());
          const isOpen = forcedOpen || openModules.has(group.module);
          const selectedCount = group.permissions.filter((permission) =>
            selected.has(permission.name),
          ).length;
          const allSelected = selectedCount === group.permissions.length;

          return (
            <section
              key={group.module}
              className="overflow-hidden rounded-xl border border-app-line bg-app-card-soft/45"
            >
              <div
                className="flex items-stretch border-b border-transparent data-[open=true]:border-app-line"
                data-open={isOpen}
              >
                <button
                  type="button"
                  onClick={() => toggleModule(group.module)}
                  className="flex min-w-0 flex-1 items-center justify-between gap-4 px-4 py-4 text-right transition hover:bg-app-card-hover"
                  aria-expanded={isOpen}
                >
                  <div className="min-w-0">
                    <h3 className="text-sm font-medium text-app-text">{group.label}</h3>
                    <p className="mt-1 text-[11px] text-app-muted-light" dir="ltr">
                      {group.module}
                    </p>
                  </div>
                  <div className="flex shrink-0 items-center gap-3">
                    <span className="rounded-full bg-app-panel-soft px-2.5 py-1 text-xs text-app-yellow">
                      {selectedCount.toLocaleString("ar")} /{" "}
                      {group.permissions.length.toLocaleString("ar")}
                    </span>
                    <ChevronDownIcon
                      className={`size-4 text-app-muted-light transition-transform ${isOpen ? "rotate-180" : ""}`}
                    />
                  </div>
                </button>
                <button
                  type="button"
                  onClick={() => toggleGroup(group.permissions)}
                  disabled={readOnly}
                  className="shrink-0 border-s border-app-line px-4 text-xs text-app-yellow transition hover:bg-app-yellow-soft"
                >
                  {allSelected ? "إلغاء الكل" : "تحديد الكل"}
                </button>
              </div>

              {isOpen && (
                <div className="grid gap-px bg-app-line sm:grid-cols-2 xl:grid-cols-3">
                  {group.permissions.map((permission) => (
                    <Checkbox
                      key={permission.name}
                      checked={selected.has(permission.name)}
                      onChange={() => togglePermission(permission.name)}
                      disabled={readOnly}
                      className="min-h-16 bg-app-panel px-4 py-3 transition hover:bg-app-card-hover"
                      labelClassName="min-w-0"
                      label={
                        <span className="block min-w-0 text-right">
                          <span className="block text-xs font-medium text-app-text">
                            {getPermissionLabel(permission)}
                          </span>
                          <bdi
                            className="mt-1 block break-all text-[10px] text-app-muted-light"
                            dir="ltr"
                          >
                            {permission.name}
                          </bdi>
                        </span>
                      }
                    />
                  ))}
                </div>
              )}
            </section>
          );
        })}

        {!groups.length && (
          <div className="flex min-h-52 items-center justify-center rounded-xl border border-dashed border-app-line text-sm text-app-muted-light">
            لا توجد صلاحيات مطابقة للبحث.
          </div>
        )}
      </div>

      <div className="sticky bottom-0 flex flex-col gap-3 border-t border-app-line bg-app-panel/95 px-5 py-4 backdrop-blur sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-center gap-2 text-xs text-app-muted-light">
          <CheckCircleIcon className={`size-4 ${isDirty ? "text-app-yellow" : "text-app-green"}`} />
          {isDirty ? "لديك تغييرات غير محفوظة" : "جميع التغييرات محفوظة"}
        </div>
        <Button
          type="button"
          className="min-w-40"
          onClick={handleSave}
          loading={isSaving}
          disabled={readOnly || !isDirty}
        >
          {readOnly ? "عرض فقط" : "حفظ الصلاحيات"}
        </Button>
      </div>
    </section>
  );
}
