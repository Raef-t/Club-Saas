"use client";

import { useEffect, useMemo, useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import ToggleSwitch from "@/components/ui/ToggleSwitch";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { useGetSubscriptionPlansQuery } from "@/lib/api/subscriptionPlansApi";
import { useCreateOfferMutation, useUpdateOfferMutation } from "@/lib/api/offersApi";
import { useToast } from "@/components/ui/Toast";
import { formatMoney } from "@/lib/utils";
import { getApiErrorMessage } from "@/lib/apiError";

export default function OfferModal({ open, onClose, offer = null, initialBranchId = "" }) {
  const toast = useToast();
  const isEditing = Boolean(offer?.id);

  const [createOffer, { isLoading: isCreating }] = useCreateOfferMutation();
  const [updateOffer, { isLoading: isUpdating }] = useUpdateOfferMutation();
  const isSubmitting = isCreating || isUpdating;

  const [form, setForm] = useState({
    branch_id: "",
    name: "",
    description: "",
    price: "",
    start_date: "",
    end_date: "",
    is_active: true,
    plans: [],
  });

  const [errors, setErrors] = useState({});
  const [generalError, setGeneralError] = useState("");

  const { data: branchesData } = useGetBranchesQuery({ all: true });
  const branches = useMemo(() => {
    if (Array.isArray(branchesData?.data?.data)) return branchesData.data.data;
    if (Array.isArray(branchesData?.data)) return branchesData.data;
    if (Array.isArray(branchesData)) return branchesData;
    return [];
  }, [branchesData]);

  // Load plans for the selected branch
  const activeBranchId = form.branch_id || initialBranchId;
  const { data: plansData, isLoading: isPlansLoading } = useGetSubscriptionPlansQuery(
    activeBranchId && activeBranchId !== "all"
      ? { branch_id: activeBranchId, all: true }
      : { all: true },
    { skip: !open }
  );

  const availablePlans = useMemo(() => {
    let list = [];
    if (Array.isArray(plansData?.data?.data)) list = plansData.data.data;
    else if (Array.isArray(plansData?.data)) list = plansData.data;
    else if (Array.isArray(plansData)) list = plansData;

    if (activeBranchId && activeBranchId !== "all") {
      list = list.filter((p) => {
        if (!p.branch_id && !p.branches) return true;
        if (p.branch_id) return String(p.branch_id) === String(activeBranchId);
        if (Array.isArray(p.branches)) {
          return p.branches.some((b) => String(b.id || b) === String(activeBranchId));
        }
        return true;
      });
    }

    return list;
  }, [plansData, activeBranchId]);

  useEffect(() => {
    if (!open) return;

    if (offer) {
      setForm({
        branch_id: String(offer.branch_id || ""),
        name: offer.name || "",
        description: offer.description || "",
        price: offer.price !== undefined ? String(offer.price) : "",
        start_date: offer.start_date || "",
        end_date: offer.end_date || "",
        is_active: offer.is_active !== undefined ? Boolean(offer.is_active) : true,
        plans: Array.isArray(offer.plans) ? offer.plans.map((p) => Number(p.id)) : [],
      });
    } else {
      const defaultBranch =
        initialBranchId && initialBranchId !== "all"
          ? String(initialBranchId)
          : branches[0]?.id
          ? String(branches[0].id)
          : "";

      setForm({
        branch_id: defaultBranch,
        name: "",
        description: "",
        price: "",
        start_date: "",
        end_date: "",
        is_active: true,
        plans: [],
      });
    }

    setErrors({});
    setGeneralError("");
  }, [open, offer, initialBranchId, branches]);

  function updateField(key, value) {
    setForm((prev) => ({ ...prev, [key]: value }));
    if (errors[key]) {
      setErrors((prev) => ({ ...prev, [key]: null }));
    }
  }

  function togglePlan(planId) {
    const idNum = Number(planId);
    setForm((prev) => {
      const exists = prev.plans.includes(idNum);
      const newPlans = exists ? prev.plans.filter((id) => id !== idNum) : [...prev.plans, idNum];
      return { ...prev, plans: newPlans };
    });
    if (errors.plans) {
      setErrors((prev) => ({ ...prev, plans: null }));
    }
  }

  const totalRegularPrice = useMemo(() => {
    return form.plans.reduce((sum, planId) => {
      const p = availablePlans.find((plan) => Number(plan.id) === Number(planId));
      return sum + (Number(p?.base_price) || 0);
    }, 0);
  }, [form.plans, availablePlans]);

  const discountAmount = useMemo(() => {
    const offerPrice = Number(form.price) || 0;
    if (totalRegularPrice <= 0 || offerPrice <= 0) return 0;
    return Math.max(0, totalRegularPrice - offerPrice);
  }, [totalRegularPrice, form.price]);

  const discountPercentage = useMemo(() => {
    if (totalRegularPrice <= 0 || discountAmount <= 0) return 0;
    return Math.round((discountAmount / totalRegularPrice) * 100);
  }, [totalRegularPrice, discountAmount]);

  async function handleSubmit(e) {
    e.preventDefault();
    setGeneralError("");

    const newErrors = {};
    if (!form.name.trim()) newErrors.name = "يرجى إدخال اسم العرض.";
    if (!form.branch_id) newErrors.branch_id = "يرجى اختيار الفرع.";
    if (!form.price || Number(form.price) < 0) newErrors.price = "يرجى إدخال سعر صالح.";
    if (form.plans.length === 0) newErrors.plans = "يرجى اختيار خطة اشتراك واحدة على الأقل.";

    if (form.start_date && form.end_date && form.start_date > form.end_date) {
      newErrors.end_date = "تاريخ النهاية لا يمكن أن يكون قبل تاريخ البداية.";
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    const payload = {
      branch_id: Number(form.branch_id),
      name: form.name.trim(),
      description: form.description.trim() || null,
      price: Number(form.price),
      start_date: form.start_date || null,
      end_date: form.end_date || null,
      is_active: form.is_active,
      plans: form.plans,
    };

    try {
      if (isEditing) {
        await updateOffer({ id: offer.id, body: payload }).unwrap();
        toast.success("تم تحديث العرض الترويجي بنجاح!");
      } else {
        await createOffer(payload).unwrap();
        toast.success("تم إنشاء العرض الترويجي الجديد بنجاح!");
      }
      onClose();
    } catch (err) {
      setGeneralError(getApiErrorMessage(err, "تعذر حفظ بيانات العرض. تحقق من الحقول وأعد المحاولة."));
    }
  }

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={isEditing ? "تعديل العرض الترويجي" : "إنشاء عرض ترويجي جديد"}
      subtitle={
        isEditing
          ? `تعديل بيانات وحزم الباقة: ${offer?.name}`
          : "تجميع باقة من خطط الاشتراكات بسعر ترويجي مخفض وموحد"
      }
      className="max-w-2xl"
    >
      <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
        {generalError && (
          <div className="rounded-xl border border-app-red/40 bg-app-red/10 p-3 text-xs text-app-red leading-relaxed">
            {generalError}
          </div>
        )}

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
          <label className="block text-right text-xs font-medium text-app-muted-light">
            الفرع <span className="text-app-red">*</span>
            <Dropdown
              className="mt-1.5 text-white"
              buttonClassName="bg-app-card-soft h-10"
              value={String(form.branch_id)}
              onChange={(val) => {
                updateField("branch_id", val);
                updateField("plans", []);
              }}
              options={branches.map((b) => ({
                value: String(b.id),
                label: b.name || `فرع #${b.id}`,
              }))}
              placeholder="اختر الفرع"
              error={errors.branch_id}
            />
          </label>

          <div className="flex items-center justify-between h-10 px-3 rounded-xl border border-app-line bg-app-card-soft/60">
            <span className="text-xs font-medium text-app-muted-light">حالة تفعيل العرض:</span>
            <ToggleSwitch
              checked={form.is_active}
              onChange={(e) => updateField("is_active", e.target.checked)}
              label={form.is_active ? "مفعل" : "معطل"}
            />
          </div>
        </div>

        <label className="block text-right text-xs font-medium text-app-muted-light">
          اسم العرض <span className="text-app-red">*</span>
          <input
            type="text"
            value={form.name}
            onChange={(e) => updateField("name", e.target.value)}
            placeholder="مثال: باقة الصيف الرياضية (سباحة + كمال أجسام)"
            className={`app-input mt-1.5 h-10 w-full px-3 text-sm text-white ${
              errors.name ? "border-app-red" : ""
            }`}
          />
          {errors.name && <span className="mt-1 block text-xs text-app-red">{errors.name}</span>}
        </label>

        <label className="block text-right text-xs font-medium text-app-muted-light">
          وصف العرض (اختياري)
          <textarea
            rows={2}
            value={form.description}
            onChange={(e) => updateField("description", e.target.value)}
            placeholder="تفاصيل العرض الترويجي والشروط الخاصة به..."
            className="app-input mt-1.5 w-full p-2.5 text-xs text-white"
          />
        </label>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <label className="block text-right text-xs font-medium text-app-muted-light">
            سعر العرض الإجمالي <span className="text-app-red">*</span>
            <input
              type="number"
              min="0"
              step="any"
              value={form.price}
              onChange={(e) => updateField("price", e.target.value)}
              placeholder="0.00"
              className={`app-input mt-1.5 h-10 w-full px-3 text-sm font-semibold text-app-yellow ${
                errors.price ? "border-app-red" : ""
              }`}
            />
            {errors.price && <span className="mt-1 block text-xs text-app-red">{errors.price}</span>}
          </label>

          <div className="flex flex-col justify-center rounded-xl border border-app-line bg-app-card-soft/50 p-3 text-xs space-y-1">
            <div className="flex justify-between text-app-muted-light">
              <span>السعر الأصلي للخطط:</span>
              <span className="font-semibold text-white">{formatMoney(totalRegularPrice)}</span>
            </div>
            {discountAmount > 0 && (
              <div className="flex justify-between text-emerald-400 font-medium">
                <span>توفير المشترك:</span>
                <span>
                  {formatMoney(discountAmount)} ({discountPercentage}%) 🏷️
                </span>
              </div>
            )}
          </div>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <DatePickerSmart
              label="تاريخ بداية العرض (اختياري)"
              value={form.start_date}
              onChange={(val) => updateField("start_date", val)}
              compact={true}
              placeholder="DD/MM/YYYY"
            />
          </div>
          <div>
            <DatePickerSmart
              label="تاريخ نهاية العرض (اختياري)"
              value={form.end_date}
              onChange={(val) => updateField("end_date", val)}
              compact={true}
              placeholder="DD/MM/YYYY"
              error={Boolean(errors.end_date)}
            />
            {errors.end_date && (
              <span className="mt-1 block text-xs text-app-red">{errors.end_date}</span>
            )}
          </div>
        </div>

        <div className="space-y-2 pt-2 border-t border-app-line/70">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-white">
              الخطط المشمولة في الباقة <span className="text-app-red">*</span>
            </span>
            <span className="text-[11px] text-app-muted-light">
              تم تحديد {form.plans.length} خطة
            </span>
          </div>

          {errors.plans && (
            <span className="block text-xs text-app-red">{errors.plans}</span>
          )}

          {isPlansLoading ? (
            <div className="text-center py-4 text-xs text-app-muted">جاري تحميل الخطط...</div>
          ) : availablePlans.length === 0 ? (
            <div className="p-3 text-center rounded-lg border border-dashed border-app-line text-xs text-app-muted">
              لا توجد خطط اشتراك مسجلة لهذا الفرع.
            </div>
          ) : (
            <div className="max-h-48 overflow-y-auto space-y-1.5 pr-1">
              {availablePlans.map((plan) => {
                const isSelected = form.plans.includes(Number(plan.id));
                return (
                  <div
                    key={plan.id}
                    onClick={() => togglePlan(plan.id)}
                    className={`flex items-center justify-between p-2.5 rounded-lg border cursor-pointer transition-colors ${
                      isSelected
                        ? "border-app-yellow/70 bg-app-yellow/10 text-white"
                        : "border-app-line bg-black/20 text-app-muted-light hover:border-app-line/80 hover:text-white"
                    }`}
                  >
                    <div className="flex items-center gap-2">
                      <input
                        type="checkbox"
                        checked={isSelected}
                        onChange={() => {}}
                        className="rounded border-app-line text-app-yellow focus:ring-0 cursor-pointer"
                      />
                      <div className="text-right">
                        <span className="text-xs font-medium block">{plan.name}</span>
                        {plan.session_count && (
                          <span className="text-[10px] text-app-muted-light">
                            {plan.session_count} حصة
                          </span>
                        )}
                      </div>
                    </div>
                    <span className="text-xs font-semibold text-app-yellow">
                      {formatMoney(plan.base_price)}
                    </span>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        <div className="flex gap-3 pt-3 border-t border-app-line">
          <Button
            type="submit"
            tone="primary"
            className="flex-1 h-10 text-sm font-semibold"
            loading={isSubmitting}
          >
            {isEditing ? "حفظ التعديلات" : "إنشاء العرض"}
          </Button>
          <Button
            type="button"
            tone="outline"
            className="h-10 px-5 text-sm"
            onClick={onClose}
            disabled={isSubmitting}
          >
            إلغاء
          </Button>
        </div>
      </form>
    </Modal>
  );
}
