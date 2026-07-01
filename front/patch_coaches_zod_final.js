const fs = require('fs');
const filePath = 'src/app/management/coaches/CoachesClient.jsx';
let content = fs.readFileSync(filePath, 'utf-8');

// Add Zod import
if (!content.includes('coachSchema')) {
    const lastImportIndex = content.lastIndexOf('import ');
    const nextLineIndex = content.indexOf('\n', lastImportIndex);
    const importStr = `\nimport { coachSchema } from "@/lib/validations/coachesSchema";`;
    content = content.slice(0, nextLineIndex) + importStr + content.slice(nextLineIndex);
}

// CoachCreateForm
if (!content.includes('const [errors, setErrors] = useState({});')) {
    content = content.replace(
        '  const [form, setForm] = useState({\n    full_name: "",',
        '  const [form, setForm] = useState({\n    full_name: "",'
    ); // just a check, actually we'll replace the closing block
    
    content = content.replace(
        '    base_salary: "3000",\n  });',
        '    base_salary: "3000",\n  });\n  const [errors, setErrors] = useState({});'
    );
}

// Clear errors
if (!content.includes('if (errors && errors[field])')) {
    content = content.replace(
        'setForm((current) => ({ ...current, [field]: value }));',
        'setForm((current) => ({ ...current, [field]: value }));\n    if (errors && errors[field]) setErrors((current) => ({ ...current, [field]: null }));'
    );
}

// handleSubmit in Create Form
const oldSubmitCreate = `  function handleSubmit(event) {
    event.preventDefault();
    onSubmit({
      full_name: form.full_name.trim(),
      gender: form.gender,
      dob: form.dob || null,
      phone: form.phone.trim() || null,
      email: form.email.trim() || null,
      address: form.address.trim() || null,
      branch_id: Number(form.branch_id),
      specialization: form.specialization.trim() || null,
      experience_years: Number(form.experience_years) || 0,
      employment_type: form.employment_type,
      base_salary: Number(form.base_salary) || 0,
    });
  }`;

const newSubmitCreate = `  function handleSubmit(event) {
    event.preventDefault();
    const data = {
      full_name: form.full_name.trim(),
      gender: form.gender,
      dob: form.dob || null,
      phone: form.phone.trim() || null,
      email: form.email.trim() || null,
      address: form.address.trim() || null,
      branch_id: Number(form.branch_id),
      specialization: form.specialization.trim() || null,
      experience_years: Number(form.experience_years) || 0,
      employment_type: form.employment_type,
      base_salary: Number(form.base_salary) || 0,
    };
    
    const result = coachSchema.safeParse(data);
    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach(issue => {
        formattedErrors[issue.path[0]] = issue.message;
      });
      setErrors(formattedErrors);
      return;
    }
    
    setErrors({});
    onSubmit(data);
  }`;
content = content.replace(oldSubmitCreate, newSubmitCreate);

// CoachEditForm
content = content.replace(
    '    experience_years: String(initialValues.details?.experience_years || 0),\n  });',
    '    experience_years: String(initialValues.details?.experience_years || 0),\n  });\n  const [errors, setErrors] = useState({});'
);

const oldSubmitEdit = `  function handleSubmit(event) {
    event.preventDefault();
    onSubmit(
      {
        base_salary: Number(form.base_salary) || 0,
        employment_type: form.employment_type,
        is_active: form.is_active,
      },
      {
        specialization: form.specialization.trim() || null,
        experience_years: Number(form.experience_years) || 0,
      },
    );
  }`;

const newSubmitEdit = `  function handleSubmit(event) {
    event.preventDefault();
    
    const data = {
      full_name: "Ignored for edit", 
      gender: "male",
      branch_id: 1,
      employment_type: form.employment_type,
      base_salary: Number(form.base_salary) || 0,
      specialization: form.specialization.trim() || null,
      experience_years: Number(form.experience_years) || 0,
    };
    
    const result = coachSchema.safeParse(data);
    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach(issue => {
        formattedErrors[issue.path[0]] = issue.message;
      });
      setErrors(formattedErrors);
      return;
    }
    
    setErrors({});
    
    onSubmit(
      {
        base_salary: Number(form.base_salary) || 0,
        employment_type: form.employment_type,
        is_active: form.is_active,
      },
      {
        specialization: form.specialization.trim() || null,
        experience_years: Number(form.experience_years) || 0,
      },
    );
  }`;
content = content.replace(oldSubmitEdit, newSubmitEdit);

// Add error props
// we can do this via regex
content = content.replace(/<Field([\s\S]*?)onChange=\{\(event\) => updateField\("([\w_]+)", event.target.value\)\}([\s\S]*?)\/>/g, 
  '<Field$1onChange={(event) => updateField("$2", event.target.value)}$3 error={errors && errors.$2}\n        />');

content = content.replace(/<Dropdown([\s\S]*?)onChange=\{\(val\) => updateField\("([\w_]+)", val\)\}([\s\S]*?)\/>/g, 
  '<Dropdown$1onChange={(val) => updateField("$2", val)}$3 error={errors && errors.$2}\n          />');

// there is no DatePickerSmart in CoachEditForm, but in Create form we use plain <input> elements actually?
// Wait, CoachesClient had native <input> tags for form previously?!
// Let's check!

fs.writeFileSync(filePath, content, 'utf-8');
console.log('CoachesClient patched properly.');
