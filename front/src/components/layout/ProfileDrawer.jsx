"use client";

import { useState } from "react";
import Drawer from "@/components/ui/Drawer";
import Button from "@/components/ui/Button";
import { useGetProfileQuery, useChangePasswordMutation, useLogoutMutation } from "@/lib/api/authApi";
import { clearAuthStorage } from "@/lib/authStorage";
import { useToast } from "@/components/ui/Toast";
import { EyeIcon, EyeOffIcon } from "@/components/icons/Icons";

export default function ProfileDrawer({ open, onClose }) {
  const toast = useToast();
  const { data: profileData, isLoading: loadingProfile } = useGetProfileQuery(undefined, {
    skip: !open,
  });
  const [changePassword, { isLoading: isChanging }] = useChangePasswordMutation();
  const [logout] = useLogoutMutation();

  const [form, setForm] = useState({
    current_password: "",
    new_password: "",
    new_password_confirmation: "",
  });

  const [showCurrent, setShowCurrent] = useState(false);
  const [showNew, setShowNew] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);

  const [formError, setFormError] = useState("");
  const [fieldErrors, setFieldErrors] = useState({});

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (fieldErrors[field]) {
      setFieldErrors((current) => ({ ...current, [field]: null }));
    }
    setFormError("");
  }

  async function handlePasswordSubmit(e) {
    e.preventDefault();
    setFormError("");
    setFieldErrors({});

    const errors = {};
    if (!form.current_password) errors.current_password = "كلمة المرور الحالية مطلوبة";
    if (!form.new_password) errors.new_password = "كلمة المرور الجديدة مطلوبة";
    if (!form.new_password_confirmation) errors.new_password_confirmation = "تأكيد كلمة المرور مطلوبة";

    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      return;
    }

    if (form.new_password.length < 6) {
      setFormError("يجب أن تكون كلمة المرور الجديدة 6 أحرف على الأقل.");
      return;
    }

    if (form.new_password !== form.new_password_confirmation) {
      setFormError("كلمة المرور الجديدة غير مطابقة لتأكيد كلمة المرور.");
      return;
    }

    try {
      await changePassword({
        current_password: form.current_password,
        new_password: form.new_password,
        new_password_confirmation: form.new_password_confirmation,
      }).unwrap();

      toast.success("تم تغيير كلمة المرور بنجاح. يرجى تسجيل الدخول بكلمة المرور الجديدة.");
      try {
        await logout().unwrap();
      } catch {
        // Ignore error if session was already terminated by backend
      }
      clearAuthStorage();
      window.location.replace("/login");
    } catch (err) {
      setFormError(err?.data?.message || "تعذر تغيير كلمة المرور. تأكد من صحة البيانات وحاول مرة أخرى.");
    }
  }

  const user = profileData?.data || {};
  const person = user.person || {};
  const fullName = person.full_name || user.username || "مستخدم تكنوجيم";
  const email = person.email || "";
  const phone = person.contacts?.[0]?.phone_number || "";
  const gender = person.gender === "male" ? "ذكر" : person.gender === "female" ? "أنثى" : "-";
  const avatarLetter = fullName.charAt(0).toUpperCase();

  return (
    <Drawer
      open={open}
      onClose={onClose}
      title="الملف الشخصي"
      subtitle="بيانات المستخدم الحالي وتحديث كلمة المرور"
      side="left"
    >
      <div className="space-y-6" dir="rtl">
        {/* User Card Info */}
        <div className="flex flex-col items-center gap-3 border-b border-app-line pb-6">
          <div className="relative grid size-20 place-items-center rounded-full bg-app-yellow text-3xl font-bold text-black uppercase shadow-lg">
            {person.photo_url ? (
              <img
                src={person.photo_url}
                className="size-full rounded-full object-cover"
                alt="Profile Avatar"
              />
            ) : (
              avatarLetter
            )}
          </div>
          <h2 className="text-lg font-semibold text-app-text">{fullName}</h2>
          <span className="rounded-full bg-app-yellow/10 px-3 py-1 text-xs text-app-yellow font-medium">
            {person.type === "staff" ? "مدير النظام" : "موظف"}
          </span>
        </div>

        {/* Detailed profile properties */}
        {loadingProfile ? (
          <div className="py-10 text-center text-sm text-app-muted-light">جاري تحميل البيانات...</div>
        ) : (
          <div className="space-y-3.5">
            <div className="flex items-center justify-between rounded-xl bg-app-card-soft/40 px-4 py-3 text-sm">
              <span className="text-app-muted-light font-medium">اسم المستخدم</span>
              <span className="text-app-text">{user.username || "-"}</span>
            </div>
            <div className="flex items-center justify-between rounded-xl bg-app-card-soft/40 px-4 py-3 text-sm">
              <span className="text-app-muted-light font-medium">البريد الإلكتروني</span>
              <span className="text-app-text">{email || "-"}</span>
            </div>
            <div className="flex items-center justify-between rounded-xl bg-app-card-soft/40 px-4 py-3 text-sm">
              <span className="text-app-muted-light font-medium">رقم الهاتف</span>
              <span className="text-app-text" dir="ltr">{phone || "-"}</span>
            </div>
            <div className="flex items-center justify-between rounded-xl bg-app-card-soft/40 px-4 py-3 text-sm">
              <span className="text-app-muted-light font-medium">الجنس</span>
              <span className="text-app-text">{gender}</span>
            </div>
          </div>
        )}

        {/* Change Password Form */}
        <div className="border-t border-app-line pt-6">
          <h3 className="text-sm font-semibold text-app-text mb-4">تغيير كلمة المرور</h3>
          
          <form noValidate onSubmit={handlePasswordSubmit} className="space-y-4">
            {/* current_password */}
            <label className="block text-right text-sm text-app-muted-light relative">
              كلمة المرور الحالية
              <div className="relative mt-2">
                <input
                  value={form.current_password}
                  type={showCurrent ? "text" : "password"}
                  dir="ltr"
                  onChange={(e) => updateField("current_password", e.target.value)}
                  className={`h-11 w-full rounded-xl border bg-app-card-soft pl-12 pr-4 text-right text-app-text outline-none transition ${
                    fieldErrors.current_password ? "border-app-red focus:border-app-red" : "border-app-line focus:border-app-yellow"
                  }`}
                  placeholder="أدخل كلمة المرور الحالية"
                />
                <button
                  type="button"
                  onClick={() => setShowCurrent(!showCurrent)}
                  className="absolute left-4 top-1/2 -translate-y-1/2 text-app-muted-light hover:text-app-text transition"
                >
                  {showCurrent ? <EyeOffIcon className="size-4" /> : <EyeIcon className="size-4" />}
                </button>
              </div>
              {fieldErrors.current_password && (
                <span className="text-app-red text-xs mt-1 block">{fieldErrors.current_password}</span>
              )}
            </label>

            {/* new_password */}
            <label className="block text-right text-sm text-app-muted-light relative">
              كلمة المرور الجديدة
              <div className="relative mt-2">
                <input
                  value={form.new_password}
                  type={showNew ? "text" : "password"}
                  dir="ltr"
                  onChange={(e) => updateField("new_password", e.target.value)}
                  className={`h-11 w-full rounded-xl border bg-app-card-soft pl-12 pr-4 text-right text-app-text outline-none transition ${
                    fieldErrors.new_password ? "border-app-red focus:border-app-red" : "border-app-line focus:border-app-yellow"
                  }`}
                  placeholder="أدخل كلمة المرور الجديدة"
                />
                <button
                  type="button"
                  onClick={() => setShowNew(!showNew)}
                  className="absolute left-4 top-1/2 -translate-y-1/2 text-app-muted-light hover:text-app-text transition"
                >
                  {showNew ? <EyeOffIcon className="size-4" /> : <EyeIcon className="size-4" />}
                </button>
              </div>
              {fieldErrors.new_password && (
                <span className="text-app-red text-xs mt-1 block">{fieldErrors.new_password}</span>
              )}
            </label>

            {/* new_password_confirmation */}
            <label className="block text-right text-sm text-app-muted-light relative">
              تأكيد كلمة المرور الجديدة
              <div className="relative mt-2">
                <input
                  value={form.new_password_confirmation}
                  type={showConfirm ? "text" : "password"}
                  dir="ltr"
                  onChange={(e) => updateField("new_password_confirmation", e.target.value)}
                  className={`h-11 w-full rounded-xl border bg-app-card-soft pl-12 pr-4 text-right text-app-text outline-none transition ${
                    fieldErrors.new_password_confirmation ? "border-app-red focus:border-app-red" : "border-app-line focus:border-app-yellow"
                  }`}
                  placeholder="أعد كتابة كلمة المرور الجديدة"
                />
                <button
                  type="button"
                  onClick={() => setShowConfirm(!showConfirm)}
                  className="absolute left-4 top-1/2 -translate-y-1/2 text-app-muted-light hover:text-app-text transition"
                >
                  {showConfirm ? <EyeOffIcon className="size-4" /> : <EyeIcon className="size-4" />}
                </button>
              </div>
              {fieldErrors.new_password_confirmation && (
                <span className="text-app-red text-xs mt-1 block">{fieldErrors.new_password_confirmation}</span>
              )}
            </label>

            {formError && (
              <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
                {formError}
              </p>
            )}

            <Button
              className="h-11 w-full text-sm font-semibold"
              type="submit"
              loading={isChanging}
              loadingLabel="جاري التحديث"
            >
              تغيير كلمة المرور
            </Button>
          </form>
        </div>
      </div>
    </Drawer>
  );
}
