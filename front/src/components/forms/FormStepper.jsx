"use client";

import "./form-stepper.css";

/**
 * A reusable multi-step indicator bar.
 *
 * @param {{ steps: { label: string }[], currentStep: number }} props
 *   `steps`       – ordered array of step descriptors (at minimum { label }).
 *   `currentStep` – zero-based index of the active step.
 */
export default function FormStepper({ steps = [], currentStep = 0 }) {
  return (
    <nav className="form-stepper" aria-label="خطوات النموذج">
      {steps.map((step, index) => {
        const isCompleted = index < currentStep;
        const isActive = index === currentStep;
        const stateKey = isCompleted ? "completed" : isActive ? "active" : "upcoming";

        return (
          <div className="form-stepper__step" key={index}>
            {/* connector line (before every step except the first) */}
            {index > 0 && (
              <span
                className={`form-stepper__connector${
                  index <= currentStep ? " form-stepper__connector--done" : ""
                }`}
                aria-hidden="true"
              />
            )}

            {/* circle */}
            <span
              className={`form-stepper__circle form-stepper__circle--${stateKey}`}
              aria-current={isActive ? "step" : undefined}
            >
              {isCompleted ? (
                <svg
                  className="form-stepper__check"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                >
                  <polyline points="20 6 9 17 4 12" />
                </svg>
              ) : (
                index + 1
              )}
            </span>

            {/* label */}
            <span className={`form-stepper__label form-stepper__label--${stateKey}`}>
              {step.label}
            </span>
          </div>
        );
      })}
    </nav>
  );
}
