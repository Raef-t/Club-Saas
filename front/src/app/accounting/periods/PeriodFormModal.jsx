"use client";

import { useEffect, useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import { Field } from "@/components/forms/FormControls";

export default function PeriodFormModal({
  isOpen,
  onClose,
  onSave,
  isLoading,
  errors = {},
}) {
  const [formData, setFormData] = useState({
    name: "",
    start_date: "",
    end_date: "",
  });

  useEffect(() => {
    if (isOpen) {
      setFormData({
        name: "",
        start_date: "",
        end_date: "",
      });
    }
  }, [isOpen]);

  const handleChange = (e) => {
    if (e?.target) {
      const { name, value } = e.target;
      setFormData((prev) => ({ ...prev, [name]: value }));
    }
  };

  const handleDateChange = (field, val) => {
    const value = typeof val === "object" && val?.target ? val.target.value : val;
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    await onSave({
      name: formData.name.trim(),
      start_date: formData.start_date,
      end_date: formData.end_date,
    });
  };

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title="إنشاء فترة مالية محاسبية جديدة"
      size="md"
    >
      <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
        <Field
          label="اسم الفترة المالية *"
          name="name"
          value={formData.name}
          onChange={handleChange}
          placeholder="مثال: شهر تموز 2026 أو الربع الثالث 2026"
          error={errors.name}
          required
        />

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Field
            label="تاريخ البداية *"
            type="date"
            name="start_date"
            value={formData.start_date}
            onChange={(val) => handleDateChange("start_date", val)}
            error={errors.start_date}
            required
          />

          <Field
            label="تاريخ النهاية *"
            type="date"
            name="end_date"
            value={formData.end_date}
            onChange={(val) => handleDateChange("end_date", val)}
            error={errors.end_date}
            required
          />
        </div>

        <div className="flex justify-end gap-3 pt-3 border-t border-app-line/30">
          <Button variant="secondary" type="button" onClick={onClose} disabled={isLoading}>
            إلغاء
          </Button>
          <Button variant="primary" type="submit" disabled={isLoading}>
            {isLoading ? "جاري الحفظ..." : "إنشاء الفترة"}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
