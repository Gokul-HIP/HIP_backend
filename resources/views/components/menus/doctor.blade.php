<div>
    <div class="text-xs font-semibold text-gray-500 mb-2">MAIN MENU</div>
    <ul class="space-y-1">
        <li>
            <a href="{{ route('doctor.dashboard.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('doctor.dashboard.*') ? 'active-menu' : '' }}">
                <i class="fa-regular fa-rectangle-list"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li>
            <a href="{{ route('doctor.my-appointment.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('doctor.my-appointment.*') ? 'active-menu' : '' }}">
                <i class="fas fa-calendar-alt"></i>
                <span>My Appointment</span>
            </a>
        </li>

        <li>
            <a href="{{ route('doctor.online-consultation.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('doctor.online-consultation.*') ? 'active-menu' : '' }}">
                <i class="fas fa-video"></i>
                <span>Online Consultation</span>
            </a>
        </li>

        <li>
            <a href="{{ route('doctor.upload-prescription.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('doctor.upload-prescription.*') ? 'active-menu' : '' }}">
                <i class="fas fa-file-prescription"></i>
                <span>Upload Prescription</span>
            </a>
        </li>

        <li>
            <a href="{{ route('doctor.member-profile.member-index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('doctor.member-profile.*') ? 'active-menu' : '' }}">
                <i class="fas fa-user"></i>
                <span>My Patients</span>
            </a>
        </li>

        <li>
            <a href="{{ route('doctor.patient-document.index') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('doctor.patient-document.*') ? 'active-menu' : '' }}">
                <i class="fa-regular fa-folder"></i>
                <span>Patient Documents</span>
            </a>
        </li>

        {{-- <li>
            <a href="{{ route('doctor.referral.send') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('doctor.referral.send*') ? 'active-menu' : '' }}">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Send Referral</span>
            </a>
        </li>

        <li>
            <a href="{{ route('doctor.referral.receive') }}"
               class="flex items-center space-x-3 p-2 rounded transition-colors {{ request()->routeIs('doctor.referral.receive*') ? 'active-menu' : '' }}">
                <i class="fa-solid fa-right-left"></i>
                <span>Receive Referral</span>
            </a>
        </li> --}}
    </ul>
</div>
