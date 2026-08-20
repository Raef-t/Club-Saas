"use client";

import { useEffect, useMemo, useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import { Field, TextAreaField } from "@/components/forms/FormControls";
import { ChevronDownIcon } from "@/components/icons/Icons";
import { ACCOUNT_TYPES, CURRENCY_OPTIONS } from "./useAccounts";

export default function AccountFormModal({
  isOpen,
  onClose,
  account,
  accounts = [],
  onSave,
  isLoading,
  errors = {},
}) {
  const [formData, setFormData] = useState({
    code: "",
    name: "",
    name_en: "",
    type: "asset",
    currency: "BOTH",
    parent_id: "",
    allow_manual_entry: true,
    is_active: true,
    description: "",
  });

  // Calculate forbidden parent IDs to prevent circular hierarchy
  const forbiddenParentIds = useMemo(() => {
    if (!account?.id) return new Set();
    const ids = new Set([account.id]);
    let added = true;
    while (added) {
      added = false;
      accounts.forEach((a) => {
        if (a.parent_id && ids.has(a.parent_id) && !ids.has(a.id)) {
          ids.add(a.id);
          added = true;
        }
      });
    }
    return ids;
  }, [account?.id, accounts]);

  // Build hierarchical list of parents with tree depth and prefixes
  const hierarchicalParents = useMemo(() => {
    const map = {};
    const roots = [];
    accounts.forEach((a) => {
      map[a.id] = { ...a, children: [] };
    });
    accounts.forEach((a) => {
      if (a.parent_id && map[a.parent_id]) {
        map[a.parent_id].children.push(map[a.id]);
      } else {
        roots.push(map[a.id]);
      }
    });

    const result = [];
    const traverse = (nodes, depth = 0) => {
      nodes.forEach((node) => {
        if (!forbiddenParentIds.has(node.id)) {
          result.push({
            id: node.id,
            code: node.code,
            name: node.name,
            type: node.type,
            currency: node.currency,
            prefix: depth === 0 ? "📁 " : `${"— ".repeat(depth)}↳ `,
          });
        }
        if (node.children && node.children.length > 0) {
          traverse(node.children, depth + 1);
        }
      });
    };
    traverse(roots);
    return result;
  }, [accounts, forbiddenParentIds]);

  useEffect(() => {
    if (account) {
      setFormData({
        code: account.code || "",
        name: account.name || "",
        name_en: account.name_en || "",
        type: account.type || "asset",
        currency: account.currency || "BOTH",
        parent_id: account.parent_id ? String(account.parent_id) : "",
        allow_manual_entry: account.allow_manual_entry ?? true,
        is_active: account.is_active ?? true,
        description: account.description || account.notes || "",
      });
    } else {
      setFormData({
        code: "",
        name: "",
        name_en: "",
        type: "asset",
        currency: "BOTH",
        parent_id: "",
        allow_manual_entry: true,
        is_active: true,
        description: "",
      });
    }
  }, [account, isOpen]);

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: type === "checkbox" ? checked : value,
    }));
  };

  const handleParentChange = (e) => {
    const parentIdStr = e.target.value;
    const parentId = parentIdStr ? Number(parentIdStr) : null;
    const parentAcc = accounts.find((a) => a.id === parentId);

    setFormData((prev) => {
      let nextType = prev.type;
      let nextCurrency = prev.currency;
      let nextCode = prev.code;

      if (parentAcc) {
        nextType = parentAcc.type;
        if (parentAcc.currency !== "BOTH") {
          nextCurrency = parentAcc.currency;
        }
        // Auto-suggest next code for new account creation
        if (!account?.id) {
          const siblings = accounts
            .filter((a) => a.parent_id === parentAcc.id && /^\d+$/.test(a.code))
            .map((a) => a.code)
            .sort();

          if (siblings.length > 0) {
            const maxSibling = siblings[siblings.length - 1];
            nextCode = String(parseInt(maxSibling, 10) + 1);
          } else {
            const parentNum = parseInt(parentAcc.code, 10);
            if (parentAcc.code.endsWith("00")) {
              nextCode = String(parentNum + 1);
            } else {
              nextCode = `${parentAcc.code}01`;
            }
          }
        }
      }

      return {
        ...prev,
        parent_id: parentIdStr,
        type: nextType,
        currency: nextCurrency,
        code: nextCode,
      };
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const payload = {
      code: formData.code.trim(),
      name: formData.name.trim(),
      name_en: formData.name_en?.trim() || null,
      type: formData.type,
      currency: formData.currency,
      parent_id: formData.parent_id ? Number(formData.parent_id) : null,
      allow_manual_entry: Boolean(formData.allow_manual_entry),
      is_active: Boolean(formData.is_active),
      description: formData.description?.trim() || null,
    };
    await onSave(payload);
  };

  const isChildAccount = Boolean(formData.parent_id);

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={account?.id ? `تعديل الحساب: ${account.code} - ${account.name}` : "إضافة حساب جديد إلى الدليل"}
      size="lg"
    >
      <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              الحساب الأب (المستوى الأعلى)
            </label>
            <div className="relative">
              <select
                name="parent_id"
                value={formData.parent_id}
                onChange={handleParentChange}
                className="h-11 w-full appearance-none rounded-xl border border-app-line bg-app-card-soft ps-3 pe-9 text-sm text-app-text outline-none focus:border-app-yellow"
              >
                <option value="" className="bg-[#1a1b22] text-[#efefef]">-- حساب رئيسي (بدون حساب أب) --</option>
                {hierarchicalParents.map((p) => (
                  <option key={p.id} value={p.id} className="bg-[#1a1b22] text-[#efefef]">
                    {p.prefix} {p.code} - {p.name} ({ACCOUNT_TYPES[p.type]?.label || p.type})
                  </option>
                ))}
              </select>
              <ChevronDownIcon className="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
            </div>
            {errors.parent_id && (
              <p className="mt-1 text-xs text-rose-500">{errors.parent_id}</p>
            )}
          </div>

          <Field
            label="رمز الحساب (الكود المحاسبي) *"
            name="code"
            value={formData.code}
            onChange={handleChange}
            placeholder="مثال: 1101"
            error={errors.code}
            required
          />

          <Field
            label="اسم الحساب (بالعربية) *"
            name="name"
            value={formData.name}
            onChange={handleChange}
            placeholder="مثال: صندوق الاستقبال الرئيسي"
            error={errors.name}
            required
          />

          <Field
            label="اسم الحساب (بالإنجليزية)"
            name="name_en"
            value={formData.name_en}
            onChange={handleChange}
            placeholder="e.g. Reception Main Cashbox"
            error={errors.name_en}
          />

          <div>
            <div className="flex items-center justify-between mb-1.5">
              <label className="text-xs font-medium text-app-muted-light">
                طبيعة الحساب (التبويب المالي) *
              </label>
              {isChildAccount && (
                <span className="text-[10px] text-app-yellow">موروث من الحساب الأب</span>
              )}
            </div>
            <div className="relative">
              <select
                name="type"
                value={formData.type}
                onChange={handleChange}
                disabled={isChildAccount}
                className={`h-11 w-full appearance-none rounded-xl border border-app-line bg-app-card-soft ps-3 pe-9 text-sm text-app-text outline-none focus:border-app-yellow ${
                  isChildAccount ? "opacity-75 cursor-not-allowed bg-app-panel" : ""
                }`}
                required
              >
                {Object.entries(ACCOUNT_TYPES).map(([key, item]) => (
                  <option key={key} value={key} className="bg-[#1a1b22] text-[#efefef]">
                    {item.label}
                  </option>
                ))}
              </select>
              <ChevronDownIcon className="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
            </div>
            {errors.type && <p className="mt-1 text-xs text-rose-500">{errors.type}</p>}
          </div>

          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              العملة المقبولة بالحساب *
            </label>
            <div className="relative">
              <select
                name="currency"
                value={formData.currency}
                onChange={handleChange}
                className="h-11 w-full appearance-none rounded-xl border border-app-line bg-app-card-soft ps-3 pe-9 text-sm text-app-text outline-none focus:border-app-yellow"
                required
              >
                {CURRENCY_OPTIONS.map((opt) => (
                  <option key={opt.value} value={opt.value} className="bg-[#1a1b22] text-[#efefef]">
                    {opt.label}
                  </option>
                ))}
              </select>
              <ChevronDownIcon className="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
            </div>
            {errors.currency && (
              <p className="mt-1 text-xs text-rose-500">{errors.currency}</p>
            )}
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
          <label className="flex items-center gap-2.5 cursor-pointer rounded-xl border border-app-line/40 bg-app-card-soft/50 p-3 hover:border-app-yellow/40 transition">
            <input
              type="checkbox"
              name="allow_manual_entry"
              checked={formData.allow_manual_entry}
              onChange={handleChange}
              className="size-4 rounded accent-app-yellow"
            />
            <div className="text-xs">
              <span className="font-semibold text-app-text block">السماح بالقيد اليدوي</span>
              <span className="text-app-muted">يسمح باختيار الحساب في سندات القيد اليومية والقبض/الصرف</span>
            </div>
          </label>

          <label className="flex items-center gap-2.5 cursor-pointer rounded-xl border border-app-line/40 bg-app-card-soft/50 p-3 hover:border-app-yellow/40 transition">
            <input
              type="checkbox"
              name="is_active"
              checked={formData.is_active}
              onChange={handleChange}
              className="size-4 rounded accent-app-yellow"
            />
            <div className="text-xs">
              <span className="font-semibold text-app-text block">حالة الحساب نشط</span>
              <span className="text-app-muted">يمكن استخدامه في العمليات والتقارير المالية الجارية</span>
            </div>
          </label>
        </div>

        <TextAreaField
          label="شرح أو ملاحظات إضافية"
          name="description"
          value={formData.description}
          onChange={handleChange}
          placeholder="تفاصيل الغرض من الحساب أو توجيهات الاستخدام..."
          rows={3}
          error={errors.description}
        />

        <div className="flex justify-end gap-3 pt-4 border-t border-app-line/30">
          <Button variant="secondary" type="button" onClick={onClose} disabled={isLoading}>
            إلغاء
          </Button>
          <Button variant="primary" type="submit" disabled={isLoading}>
            {isLoading ? "جاري الحفظ..." : account?.id ? "حفظ التعديلات" : "إضافة الحساب"}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
