"use client";

import Modal from "@/components/ui/Modal";
import { SubscriptionCreateForm } from "./SubscriptionForm";

/**
 * Modal dialog for creating a new member subscription.
 */
export default function AddSubscriptionModal({
  open,
  onClose,
  title = "إضافة اشتراك جديد",
  subtitle = "",
  ...props
}) {
  return (
    <Modal
      open={open}
      onClose={props.isLoading ? undefined : onClose}
      title={title}
      subtitle={subtitle}
      className="max-w-2xl"
    >
      <SubscriptionCreateForm
        {...props}
        onCancel={onClose}
      />
    </Modal>
  );
}
