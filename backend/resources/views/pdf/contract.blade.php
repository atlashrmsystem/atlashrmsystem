<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Employment Contract / عقد عمل</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; color: #1E6CC7; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #F4F6F9; }
        .dual-column { width: 50%; vertical-align: top; padding: 10px; }
        .arabic { text-align: right; direction: rtl; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">ATLAS Group</h1>
        <h2>Fixed-Term Employment Contract<br><span class="arabic">عقد عمل محدد المدة</span></h2>
        <p>In accordance with UAE Federal Decree-Law No. 33 of 2021</p>
    </div>

    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td class="dual-column">
                <h3>Employer Details</h3>
                <p><strong>Company Name:</strong> ATLAS Group</p>
                <p><strong>Location:</strong> United Arab Emirates</p>
            </td>
            <td class="dual-column arabic">
                <h3>تفاصيل صاحب العمل</h3>
                <p><strong>اسم الشركة:</strong> مجموعة أطلس</p>
                <p><strong>الموقع:</strong> الإمارات العربية المتحدة</p>
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td class="dual-column">
                <h3>Employee Details</h3>
                <p><strong>Name:</strong> {{ $employee->full_name }}</p>
                <p><strong>Passport Number:</strong> {{ $employee->passport_number }}</p>
                <p><strong>Job Title:</strong> {{ $employee->job_title }}</p>
                <p><strong>Department:</strong> {{ $employee->department }}</p>
                <p><strong>Basic Salary (AED):</strong> {{ number_format((float) $employee->basic_salary, 2) }}</p>
            </td>
            <td class="dual-column arabic">
                <h3>تفاصيل الموظف</h3>
                <p><strong>الاسم:</strong> {{ $employee->full_name }}</p>
                <p><strong>رقم الجواز:</strong> {{ $employee->passport_number }}</p>
                <p><strong>المسمى الوظيفي:</strong> {{ $employee->job_title }}</p>
                <p><strong>القسم:</strong> {{ $employee->department }}</p>
                <p><strong>الراتب الأساسي (درهم):</strong> {{ number_format((float) $employee->basic_salary, 2) }}</p>
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <tr>
            <td class="dual-column">
                <h3>Contract Terms</h3>
                <p><strong>Contract Type:</strong> {{ $contract->type }}</p>
                <p><strong>Start Date:</strong> {{ \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') }}</p>
                <p><strong>End Date:</strong> {{ \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') }}</p>
                <p><em>This contract duration does not exceed 3 years as per UAE Labor Law.</em></p>
            </td>
            <td class="dual-column arabic">
                <h3>شروط العقد</h3>
                <p><strong>نوع العقد:</strong> محدد المدة</p>
                <p><strong>تاريخ البدء:</strong> {{ \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') }}</p>
                <p><strong>تاريخ الانتهاء:</strong> {{ \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') }}</p>
                <p><em>مدة هذا العقد لا تتجاوز 3 سنوات وفقاً لقانون العمل الإماراتي.</em></p>
            </td>
        </tr>
    </table>

    <div style="margin-top: 50px;">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 50%; text-align: center; border: none;">
                    ________________________<br>
                    <strong>Employer Signature / توقيع صاحب العمل</strong>
                </td>
                <td style="width: 50%; text-align: center; border: none;">
                    ________________________<br>
                    <strong>Employee Signature / توقيع الموظف</strong>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
