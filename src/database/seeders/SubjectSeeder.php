<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            // Computer Science Subjects
            'Introduction to Programming',
            'Data Structures and Algorithms',
            'Object-Oriented Programming',
            'Database Management Systems',
            'Web Development Fundamentals',
            'Mobile Application Development',
            'Software Engineering Principles',
            'Computer Networks and Communications',
            'Operating Systems',
            'Artificial Intelligence',
            'Machine Learning Fundamentals',
            'Cybersecurity and Information Assurance',
            'Computer Graphics and Visualization',
            'Software Quality Assurance and Testing',

            // Information Technology Subjects
            'IT Fundamentals and Hardware',
            'Systems Analysis and Design',
            'Network Administration and Security',
            'Cloud Computing Architecture',
            'IT Service Management',
            'Enterprise Resource Planning',

            // Engineering Subjects
            'Engineering Mathematics I',
            'Engineering Mathematics II',
            'Physics for Engineers',
            'Chemistry for Engineers',
            'Engineering Mechanics',
            'Electrical Circuits and Systems',
            'Digital Logic Design',
            'Microprocessors and Microcontrollers',
            'Control Systems Engineering',
            'Engineering Economics and Management',
            'Engineering Ethics and Professional Practice',

            // Business Administration Subjects
            'Principles of Management',
            'Financial Accounting',
            'Cost Accounting and Control',
            'Marketing Management',
            'Human Resource Management',
            'Business Statistics and Analytics',
            'Operations and Supply Chain Management',
            'Entrepreneurship and Innovation',
            'Business Law and Ethics',
            'Strategic Management',

            // General Education Subjects
            'English Communication Skills',
            'Technical Writing and Presentation',
            'Mathematics in the Modern World',
            'Science, Technology and Society',
            'Philippine History and Governance',
            'Life and Works of Rizal',
            'Physical Education and Wellness',
            'Art Appreciation and Humanities',
            'Ethics and Moral Philosophy',
            'Environmental Science and Sustainability',
            'Understanding the Self',
            'Readings in Philippine History',
        ];

        foreach ($subjects as $subject) {
            Subject::updateOrCreate(
                ['subject' => $subject],
                ['active' => true]
            );
        }
    }
}
