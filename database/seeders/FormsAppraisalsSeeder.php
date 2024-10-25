<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FormAppraisal;
use App\Models\FormGroupAppraisal;

class FormsAppraisalsSeeder extends Seeder
{
    public function run()
    {
        // Create KPI Form
        $kpiForm = FormAppraisal::create([
            'name' => 'KPI',
            'category' => 'Appraisal',
            'title' => 'Score Guideline',
            'data' => [],
            'icon' => 'ri-user-fill',
            'blade' => 'pages/appraisals/self-review',
            'created_by' => 0
        ]);

        // Create Culture Form
        $cultureForm = FormAppraisal::create([
            'name' => 'Culture',
            'category' => 'Appraisal',
            'title' => 'Indicator level',
            'data' => [
                [
                    'title' => 'SYNERGIZED TEAM',
                    'description' => 'Dorongan atau kemampuan untuk bekerja sama, bersikap pengertian dan penerimaan terhadap orang lain serta peka terhadap kontribusi orang lain untuk mengumpulkan informasi lebih banyak sehubungan dengan pelaksanaan pekerjaan dan pengambilan keputusan guna mencapai komitmen bersama',
                    'items' => [
                        'Secara konsisten mampu mengajak orang lain untuk saling menghargai dan bekerja sama dalam mencapai tujuan bersama',
                        'Mampu membagikan informasi yang relevan sehubungan pekerjaan dalam tim',
                        'Mampu menunjukkan kepekaan terhadap kebutuhan kerja anggota kelompoknya'
                    ],
                    'score' => []
                ],
                [
                    'title' => 'INTEGRITY FOR ALL ACTION',
                    'description' => 'Taat dan loyal kepada hukum dan nilai perusahaan, dapat dipercaya dan diandalkan',
                    'items' => [
                        'Mampu bertindak secara konsisten dengan nilai-nilai dan prinsip-prinsip etika',
                        'Mampu mempertahankan komitmen dan disiplin terhadap peraturan dan norma Perusahaan',
                        'Mampu menempatkan kepentingan Perusahaan diatas kepentingan pribadi'
                    ],
                    'score' => []
                ],
                [
                    'title' => 'GROWTH FOR CO PROSPERITY',
                    'description' => 'Dorongan dalam diri untuk menjaga harmonisasi dalam upaya mencapai keberhasilan bersama yang berkelanjutan',
                    'items' => [
                        'Mampu berperan aktif dalam menjaga harmonisasi tim dengan cara menghormati dan menghargai perbedaan pendapat antar rekan kerja serta berusaha memahami sudut pandang orang lain',
                        'Mampu melaksanakan pekerjaan yang ada dengan cara-cara yang sesuai dengan prinsip \'menang bersama\'',
                        'Memperlihatkan perilaku kerja yang berorientasi pada keberlangsungan jangka panjang perusahaan'
                    ],
                    'score' => []
                ]
            ],
            'icon' => 'ri-user-settings-fill',
            'blade' => 'pages/appraisals/culture',
            'created_by' => 0
        ]);

        // Create Leadership Form
        $leadershipForm = FormAppraisal::create([
            'name' => 'Leadership',
            'category' => 'Appraisal',
            'title' => '',
            'data' => [
                [
                    'title' => 'Manage & Planning',
                    'description' => 'Secara efektif merencanakan dan mengorganisir pekerjaan sesuai kebutuhan organisasi dengan menetapkan tujuan dan mengantisipasi kebutuhan dan prioritas',
                    'items' => [
                        'Mampu menyusun rencana kerja jangka pendek-menengah dengan target yang jelas, terukur dan obyektif untuk tim yang dipimpin serta mampu mengusulkan kebutuhan sumber daya yang diperlukan untuk mengimplementasikan rencana kerja tersebut',
                        'Menunjukkan kemampuan time management dengan cara menyusun prioritas dalam rangka menyelesaikan tugas-tugas yang ada, mengarahkan dan memastikan anggota tim bekerja sesuai dengan prioritas yang telah disusun serta mampu menyusun sistem dan mekanisme kontrol sehingga secara simultan mampu menyelesaikan pekerjaan-pekerjaan yang ada tepat waktu dengan hasil sesuai harapan'
                    ],
                    'score' => []
                ],
                [
                    'title' => 'Decision Making',
                    'description' => 'Mampu mendefinisikan setiap keputusan dari berbagai sudut pandang serta mengajak orang lain untuk turut serta mengambil keputusan secara efektif',
                    'items' => [
                        'Mampu melihat dan menganalisa masalah dan isu jangka pendek-menengah dari berbagai sudut pandang dalam proses pengambilan keputusan di ruang lingkup kerjanya serta mampu mengambil keputusan dengan dampak jangka pendek-menengah dengan menggunakan proyeksi untung rugi dan mempertimbangkan faktor efisiensi dalam proses pengambilan keputusan',
                        'Mampu menciptakan keterlibatan tim atau pihak-pihak terkait di dalam proses pengambilan dan pelaksanaan keputusan dalam section/department yg dipimpin'
                    ],
                    'score' => []
                ],
                [
                    'title' => 'Developing Others',
                    'description' => 'Membuat perencanaan dan mendukung pengembangan kemampuan dan keterampilan individu sehingga mereka dapat memenuhi tanggung jawab pekerjaan/peran saat ini atau di masa datang dengan lebih efektif',
                    'items' => [
                        'Mampu mengidentifikasi kekuatan dan kelemahan anggota tim serta menyesuaikan support yang diberikan kepada anggota tim',
                        'Secara proaktif menggunakan kesempatan yang ada untuk membangun pertumbuhan anggota tim serta secara regular memberikan umpan balik yang membangun dan saran pengembangan diri yang dapat dilaksanakan oleh seluruh anggota tim'
                    ],
                    'score' => []
                ]
            ],
            'icon' => 'ri-team-fill',
            'blade' => 'pages/appraisals/leadership',
            'created_by' => 0
        ]);

        // Create Form Group
        $formGroup = FormGroupAppraisal::create([
            'name' => 'Appraisal Form',
            'form_number' => 3,
            'form_names' => ['KPI', 'Culture', 'Leadership'],
            'restrict' => [
                'job level' => ['4A', '4B']
            ],
            'created_by' => 0
        ]);

        // Link forms to group with sort order
        $formGroup->formAppraisals()->attach([
            $kpiForm->id => ['sort_order' => 1, 'created_by' => 0],
            $cultureForm->id => ['sort_order' => 2, 'created_by' => 0],
            $leadershipForm->id => ['sort_order' => 3, 'created_by' => 0]
        ]);
    }
}
