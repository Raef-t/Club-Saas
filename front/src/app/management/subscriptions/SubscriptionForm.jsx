"use client";

import { useState } from "react";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import { TrashIcon } from "@/components/icons/Icons";
import { useToast } from "@/components/ui/Toast";
import { formatLocalizedName } from "@/lib/utils";
import { subscriptionSchema } from "@/lib/validations/subscriptionsSchema";
import { formatSubscriptionMoney } from "./subscriptionUtils";

const CURRENCY_SYMBOL = "$";

/**
 * Collects and validates the values required to create a member subscription.
 */
export function SubscriptionCreateForm({
  members = [],
  plans = [],
  activities = [],
  coaches = [],
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
  formId,
  showFooterActions = true,
  formClassName = "space-y-4",
}) {
  const toast = useToast();
  const [form, setForm] = useState({
    member_id: members[0]?.id ? String(members[0].id) : "",
    plan_id: plans[0]?.id ? String(plans[0].id) : "",
    paid_amount: plans[0]?.base_price ? String(plans[0].base_price) : "0",
    start_date: "",
  });
  const [errors, setErrors] = useState({});

  const [selectedActivities, setSelectedActivities] = useState([]);
  const [currActivityId, setCurrActivityId] = useState("");
  const [currCoachId, setCurrCoachId] = useState("");

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors && errors[field]) setErrors((current) => ({ ...current, [field]: null }));
  }

  function handleAddActivity() {
    if (!currActivityId || !currCoachId) return;

    // يمنع إسناد النشاط نفسه أكثر من مرة داخل الاشتراك.
    if (selectedActivities.some((item) => String(item.activity_id) === String(currActivityId))) {
      toast.warning("تمت إضافة هذا النشاط مسبقاً.");
      return;
    }

    const activityObj = activities.find((a) => String(a.id) === String(currActivityId));
    const coachObj = coaches.find((c) => String(c.id) === String(currCoachId));

    setSelectedActivities((prev) => [
      ...prev,
      {
        activity_id: Number(currActivityId),
        coach_id: Number(currCoachId),
        activityName: activityObj
          ? typeof activityObj.name === "string"
            ? activityObj.name
            : activityObj.name?.ar || activityObj.name?.en
          : "",
        coachName: coachObj?.person?.full_name || `مدرب #${currCoachId}`,
      },
    ]);

    setCurrActivityId("");
    setCurrCoachId("");
  }

  function handleRemoveActivity(actId) {
    setSelectedActivities((prev) => prev.filter((item) => item.activity_id !== actId));
  }

  function handleSubmit(event) {
    event.preventDefault();

    const validationData = {
      member_id: Number(form.member_id),
      plan_id: Number(form.plan_id),
      paid_amount: Number(form.paid_amount) || 0,
      start_date: form.start_date || "",
    };

    const result = subscriptionSchema.safeParse(validationData);
    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach((issue) => {
        formattedErrors[issue.path.join("_")] = issue.message;
      });
      setErrors(formattedErrors);
      return;
    }

    setErrors({});
    onSubmit({
      ...validationData,
      payment_method: "cash",
      activities: selectedActivities.map((act) => ({
        activity_id: act.activity_id,
        coach_id: act.coach_id,
      })),
    });
  }

  const selectedPlanObj = plans.find((p) => String(p.id) === String(form.plan_id));

  return (
    <form id={formId} noValidate onSubmit={handleSubmit} className={formClassName} dir="rtl">
      <label className="block text-right text-sm text-app-muted-light">
        اللاعب العضو
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.member_id}
          onChange={(val) => updateField("member_id", val)}
          options={members.map((m) => ({
            value: String(m.id),
            label: `${m.person?.full_name || `${m.first_name || ""} ${m.last_name || ""}`} (رقم العضوية: #${m.id})`,
          }))}
          placeholder="اختر اللاعب"
          error={errors && errors.member_id}
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        خطة الاشتراك
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.plan_id}
          onChange={(val) => {
            updateField("plan_id", val);
            const planObj = plans.find((p) => String(p.id) === String(val));
            if (planObj) {
              updateField("paid_amount", String(planObj.base_price || "0"));
            }
          }}
          options={plans.map((p) => ({
            value: String(p.id),
            label: `${formatLocalizedName(p.name) || p.name || ""} - ${formatSubscriptionMoney(p.base_price)}`,
          }))}
          placeholder="اختر الخطة"
          error={errors && errors.plan_id}
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        المبلغ المدفوع للاشتراك ({CURRENCY_SYMBOL})
        <input
          type="number"
          min="0"
          value={form.paid_amount}
          onChange={(e) => updateField("paid_amount", e.target.value)}
          aria-invalid={Boolean(errors && errors.paid_amount)}
          className={`app-input mt-2 h-11 w-full px-3 text-right outline-none bg-app-card-soft text-white ${
            errors && errors.paid_amount
              ? "border border-app-red focus:border-app-red"
              : "focus:border-app-yellow/70"
          }`}
          placeholder={selectedPlanObj ? `السعر الأساسي: ${selectedPlanObj.base_price}` : ""}
          required
        />
        {errors && errors.paid_amount && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {errors.paid_amount}
          </span>
        )}
      </label>

      <div>
        <DatePickerSmart
          label="تاريخ بداية الاشتراك *"
          value={form.start_date}
          onChange={(val) => updateField("start_date", val)}
          compact={false}
          error={errors && errors.start_date}
        />
        {errors && errors.start_date && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {errors.start_date}
          </span>
        )}
      </div>

      {/* يتيح إسناد مدرب مستقل لكل نشاط ضمن الاشتراك. */}
      <div className="border-t border-app-line pt-4 mt-2 text-right">
        <h4 className="text-sm font-semibold text-white mb-3">
          الأنشطة والمدربين المنسوبين للاشتراك
        </h4>

        {selectedActivities.length > 0 && (
          <div className="space-y-2 mb-3">
            {selectedActivities.map((act) => (
              <div
                key={act.activity_id}
                className="flex items-center justify-between p-2 rounded bg-black/35 text-xs text-app-text border border-app-line"
              >
                <button
                  type="button"
                  onClick={() => handleRemoveActivity(act.activity_id)}
                  className="text-app-red p-1 rounded hover:bg-app-red/10 transition"
                >
                  <TrashIcon className="size-4" />
                </button>
                <span>
                  {act.activityName} · المدرب: {act.coachName}
                </span>
              </div>
            ))}
          </div>
        )}

        <div className="flex gap-2 items-end bg-app-card-soft/40 p-3 rounded-xl border border-app-line">
          <div className="flex-1 space-y-2">
            <label className="block text-xs text-app-muted-light">اختر النشاط</label>
            <Dropdown
              className="text-white bg-black/45 rounded text-xs"
              buttonClassName="h-9"
              value={currActivityId}
              onChange={setCurrActivityId}
              options={activities.map((a) => ({
                value: String(a.id),
                label: typeof a.name === "string" ? a.name : a.name?.ar || a.name?.en || "",
              }))}
              placeholder="الرياضة / النشاط"
            />
          </div>

          <div className="flex-1 space-y-2">
            <label className="block text-xs text-app-muted-light">اختر المدرب</label>
            <Dropdown
              className="text-white bg-black/45 rounded text-xs"
              buttonClassName="h-9"
              value={currCoachId}
              onChange={setCurrCoachId}
              options={coaches.map((c) => ({
                value: String(c.id),
                label: c.person?.full_name || `مدرب #${c.id}`,
              }))}
              placeholder="المدرب الكوتش"
            />
          </div>

          <Button
            type="button"
            className="h-9 px-3 text-xs"
            disabled={!currActivityId || !currCoachId}
            onClick={handleAddActivity}
          >
            إضافة
          </Button>
        </div>
      </div>

      {errorMessage && (
        <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
          {errorMessage}
        </p>
      )}

      <div className={`${showFooterActions ? "flex" : "entry-form-actions-hidden"} gap-3 pt-2`}>
        <Button type="button" tone="outline" className="h-11 flex-1" onClick={onCancel}>
          إلغاء
        </Button>
        <Button type="submit" className="h-11 flex-1" loading={isLoading}>
          إنشاء الاشتراك
        </Button>
      </div>
    </form>
  );
}
