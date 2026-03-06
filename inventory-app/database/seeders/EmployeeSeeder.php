<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Employees sourced from ML-Dataset.csv (first 100 unique records)
     * Format: [Name, Email, Phone, JobTitle, WarehouseName, HireDate]
     */
    public function run(): void
    {
        $data = [
            ['Summer Payne', 'summer.payne@example.com', '5151238181', 'Public Accountant', 'Southlake Texas', '2016-06-07'],
            ['Rose Stephens', 'rose.stephens@example.com', '5151238080', 'Accounting Manager', 'Southlake Texas', '2016-06-07'],
            ['Annabelle Dunn', 'annabelle.dunn@example.com', '5151234444', 'Administration Assistant', 'Southlake Texas', '2016-09-17'],
            ['Tommy Bailey', 'tommy.bailey@example.com', '5151234567', 'President', 'Southlake Texas', '2016-06-17'],
            ['Blake Cooper', 'blake.cooper@example.com', '5151234569', 'Administration Vice President', 'Southlake Texas', '2016-01-13'],
            ['Jude Rivera', 'jude.rivera@example.com', '5151234568', 'Administration Vice President', 'Southlake Texas', '2016-09-21'],
            ['Tyler Ramirez', 'tyler.ramirez@example.com', '5151244269', 'Accountant', 'Southlake Texas', '2016-09-28'],
            ['Ryan Gray', 'ryan.gray@example.com', '5151244169', 'Accountant', 'Southlake Texas', '2016-08-16'],
            ['Elliot Brooks', 'elliot.brooks@example.com', '5151244567', 'Accountant', 'Southlake Texas', '2016-12-07'],
            ['Elliott James', 'elliott.james@example.com', '5151244369', 'Accountant', 'Southlake Texas', '2016-09-30'],
            ['Albert Watson', 'albert.watson@example.com', '5151244469', 'Accountant', 'Southlake Texas', '2016-03-07'],
            ['Mohammad Peterson', 'mohammad.peterson@example.com', '5151244569', 'Finance Manager', 'Southlake Texas', '2016-08-17'],
            ['Harper Spencer', 'harper.spencer@example.com', '5151237777', 'Human Resources Representative', 'Southlake Texas', '2016-06-07'],
            ['Louie Richardson', 'louie.richardson@example.com', '5904234567', 'Programmer', 'Southlake Texas', '2016-01-03'],
            ['Nathan Cox', 'nathan.cox@example.com', '5904234568', 'Programmer', 'Southlake Texas', '2016-05-21'],
            ['Bobby Torres', 'bobby.torres@example.com', '5904235567', 'Programmer', 'Southlake Texas', '2016-02-07'],
            ['Charles Ward', 'charles.ward@example.com', '5904234560', 'Programmer', 'Southlake Texas', '2016-02-05'],
            ['Gabriel Howard', 'gabriel.howard@example.com', '59042345692', 'Programmer', 'Southlake Texas', '2016-06-25'],
            ['Emma Perkins', 'emma.perkins@example.com', '5151235555', 'Marketing Manager', 'Southlake Texas', '2016-02-17'],
            ['Amelie Hudson', 'amelie.hudson@example.com', '6031236666', 'Marketing Representative', 'Southlake Texas', '2016-08-17'],
            ['Gracie Gardner', 'gracie.gardner@example.com', '5151238888', 'Public Relations Representative', 'Southlake Texas', '2016-06-07'],
            ['Frederick Price', 'frederick.price@example.com', '5151274563', 'Purchasing Clerk', 'Southlake Texas', '2016-12-24'],
            ['Alex Sanders', 'alex.sanders@example.com', '5151274562', 'Purchasing Clerk', 'Southlake Texas', '2016-05-18'],
            ['Ollie Bennett', 'ollie.bennett@example.com', '5151274564', 'Purchasing Clerk', 'Southlake Texas', '2016-07-24'],
            ['Louis Wood', 'louis.wood@example.com', '5151274565', 'Purchasing Clerk', 'Southlake Texas', '2016-11-15'],
            ['Dexter Barnes', 'dexter.barnes@example.com', '5151274566', 'Purchasing Clerk', 'Southlake Texas', '2016-08-10'],
            ['Rory Kelly', 'rory.kelly@example.com', '5151274561', 'Purchasing Manager', 'Southlake Texas', '2016-12-07'],
            ['Isabella Cole', 'isabella.cole@example.com', '11441344619268', 'Sales Manager', 'Southlake Texas', '2016-10-15'],
            ['Jessica Woods', 'jessica.woods@example.com', '11441344429278', 'Sales Manager', 'Southlake Texas', '2016-03-10'],
            ['Ella Wallace', 'ella.wallace@example.com', '11441344467268', 'Sales Manager', 'Southlake Texas', '2016-01-05'],
            ['Ava Sullivan', 'ava.sullivan@example.com', '11441344429268', 'Sales Manager', 'Southlake Texas', '2016-10-01'],
            ['Mia West', 'mia.west@example.com', '11441344429018', 'Sales Manager', 'Southlake Texas', '2016-01-29'],
            ['Evie Harrison', 'evie.harrison@example.com', '11441344486508', 'Sales Representative', 'Southlake Texas', '2016-11-23'],
            ['Scarlett Gibson', 'scarlett.gibson@example.com', '11441345429268', 'Sales Representative', 'Southlake Texas', '2016-01-30'],
            ['Ruby Mcdonald', 'ruby.mcdonald@example.com', '11441345929268', 'Sales Representative', 'Southlake Texas', '2016-03-04'],
            ['Chloe Cruz', 'chloe.cruz@example.com', '11441345829268', 'Sales Representative', 'Southlake Texas', '2016-08-01'],
            ['Isabelle Marshall', 'isabelle.marshall@example.com', '11441345729268', 'Sales Representative', 'Southlake Texas', '2016-03-10'],
            ['Daisy Ortiz', 'daisy.ortiz@example.com', '11441345629268', 'Sales Representative', 'Southlake Texas', '2016-12-15'],
            ['Freya Gomez', 'freya.gomez@example.com', '11441345529268', 'Sales Representative', 'Southlake Texas', '2016-11-03'],
            ['Elizabeth Dixon', 'elizabeth.dixon@example.com', '11441644429262', 'Sales Representative', 'Southlake Texas', '2016-01-04'],
            ['Florence Freeman', 'florence.freeman@example.com', '11441346229268', 'Sales Representative', 'Southlake Texas', '2016-03-19'],
            ['Alice Wells', 'alice.wells@example.com', '11441346329268', 'Sales Representative', 'Southlake Texas', '2016-01-24'],
            ['Charlotte Webb', 'charlotte.webb@example.com', '11441346529268', 'Sales Representative', 'Southlake Texas', '2016-02-23'],
            ['Sienna Simpson', 'sienna.simpson@example.com', '11441346629268', 'Sales Representative', 'Southlake Texas', '2016-03-24'],
            ['Matilda Stevens', 'matilda.stevens@example.com', '11441346729268', 'Sales Representative', 'San Francisco', '2016-04-21'],
            ['Evelyn Tucker', 'evelyn.tucker@example.com', '11441343929268', 'Sales Representative', 'San Francisco', '2016-03-11'],
            ['Eva Porter', 'eva.porter@example.com', '11441343829268', 'Sales Representative', 'San Francisco', '2016-03-23'],
            ['Millie Hunter', 'millie.hunter@example.com', '11441343729268', 'Sales Representative', 'San Francisco', '2016-01-24'],
            ['Sofia Hicks', 'sofia.hicks@example.com', '11441343629268', 'Sales Representative', 'San Francisco', '2016-02-23'],
            ['Lucy Crawford', 'lucy.crawford@example.com', '11441343529268', 'Sales Representative', 'San Francisco', '2016-03-24'],
            ['Elsie Henry', 'elsie.henry@example.com', '11441343329268', 'Sales Representative', 'San Francisco', '2016-04-21'],
            ['Imogen Boyd', 'imogen.boyd@example.com', '11441644429267', 'Sales Representative', 'San Francisco', '2016-05-11'],
            ['Layla Mason', 'layla.mason@example.com', '11441644429266', 'Sales Representative', 'San Francisco', '2016-03-19'],
            ['Rosie Morales', 'rosie.morales@example.com', '11441644429265', 'Sales Representative', 'San Francisco', '2016-03-24'],
            ['Maya Kennedy', 'maya.kennedy@example.com', '11441644429264', 'Sales Representative', 'San Francisco', '2016-04-23'],
            ['Esme Warren', 'esme.warren@example.com', '11441644429263', 'Sales Representative', 'San Francisco', '2016-05-24'],
            ['Grace Ellis', 'grace.ellis@example.com', '11441344987668', 'Sales Representative', 'San Francisco', '2016-12-09'],
            ['Lily Fisher', 'lily.fisher@example.com', '11441344498718', 'Sales Representative', 'San Francisco', '2016-03-30'],
            ['Sophia Reynolds', 'sophia.reynolds@example.com', '11441344478968', 'Sales Representative', 'San Francisco', '2016-08-20'],
            ['Sophie Owens', 'sophie.owens@example.com', '11441344345268', 'Sales Representative', 'San Francisco', '2016-03-24'],
            ['Poppy Jordan', 'poppy.jordan@example.com', '11441344129268', 'Sales Representative', 'San Francisco', '2016-01-30'],
            ['Phoebe Murray', 'phoebe.murray@example.com', '11441346129268', 'Sales Representative', 'San Francisco', '2016-11-11'],
            ['Holly Shaw', 'holly.shaw@example.com', '6505091876', 'Shipping Clerk', 'San Francisco', '2016-01-27'],
            ['Emilia Holmes', 'emilia.holmes@example.com', '6505092876', 'Shipping Clerk', 'San Francisco', '2016-02-20'],
            ['Molly Rice', 'molly.rice@example.com', '6505093876', 'Shipping Clerk', 'San Francisco', '2016-06-24'],
            ['Ellie Robertson', 'ellie.robertson@example.com', '6505094876', 'Shipping Clerk', 'San Francisco', '2016-02-07'],
            ['Jasmine Hunt', 'jasmine.hunt@example.com', '6505051876', 'Shipping Clerk', 'San Francisco', '2016-06-14'],
            ['Eliza Black', 'eliza.black@example.com', '6505052876', 'Shipping Clerk', 'San Francisco', '2016-08-13'],
            ['Lilly Daniels', 'lilly.daniels@example.com', '6505053876', 'Shipping Clerk', 'San Francisco', '2016-07-11'],
            ['Abigail Palmer', 'abigail.palmer@example.com', '6505054876', 'Shipping Clerk', 'San Francisco', '2016-12-19'],
            ['Georgia Mills', 'georgia.mills@example.com', '6505011876', 'Shipping Clerk', 'San Francisco', '2016-02-04'],
            ['Maisie Nichols', 'maisie.nichols@example.com', '6505012876', 'Shipping Clerk', 'San Francisco', '2016-03-03'],
            ['Eleanor Grant', 'eleanor.grant@example.com', '6505013876', 'Shipping Clerk', 'San Francisco', '2016-07-01'],
            ['Hannah Knight', 'hannah.knight@example.com', '6505014876', 'Shipping Clerk', 'San Francisco', '2016-03-17'],
            ['Harriet Ferguson', 'harriet.ferguson@example.com', '6505079811', 'Shipping Clerk', 'San Francisco', '2016-04-24'],
            ['Amber Rose', 'amber.rose@example.com', '6505079822', 'Shipping Clerk', 'San Francisco', '2016-05-23'],
            ['Bella Stone', 'bella.stone@example.com', '6505079833', 'Shipping Clerk', 'San Francisco', '2016-06-21'],
            ['Thea Hawkins', 'thea.hawkins@example.com', '6505079844', 'Shipping Clerk', 'San Francisco', '2016-01-13'],
            ['Lola Ramos', 'lola.ramos@example.com', '6505079876', 'Shipping Clerk', 'San Francisco', '2016-01-24'],
            ['Willow Reyes', 'willow.reyes@example.com', '6505079877', 'Shipping Clerk', 'San Francisco', '2016-02-23'],
            ['Ivy Burns', 'ivy.burns@example.com', '6505079878', 'Shipping Clerk', 'San Francisco', '2016-06-21'],
            ['Erin Gordon', 'erin.gordon@example.com', '6505079879', 'Shipping Clerk', 'San Francisco', '2016-02-03'],
            ['Reggie Simmons', 'reggie.simmons@example.com', '6501248234', 'Stock Clerk', 'San Francisco', '2016-04-10'],
            ['Emily Hamilton', 'emily.hamilton@example.com', '6501212874', 'Stock Clerk', 'San Francisco', '2016-03-15'],
            ['Olivia Ford', 'olivia.ford@example.com', '6501212994', 'Stock Clerk', 'San Francisco', '2016-01-29'],
            ['Amelia Myers', 'amelia.myers@example.com', '6501218009', 'Stock Clerk', 'San Francisco', '2016-10-17'],
            ['Connor Hayes', 'connor.hayes@example.com', '6501211834', 'Stock Clerk', 'San Francisco', '2016-04-06'],
            ['Leon Powell', 'leon.powell@example.com', '6501241214', 'Stock Clerk', 'San Francisco', '2016-07-16'],
            ['Kai Long', 'kai.long@example.com', '6501241224', 'Stock Clerk', 'New Jersy', '2016-09-28'],
            ['Aaron Patterson', 'aaron.patterson@example.com', '6501241334', 'Stock Clerk', 'New Jersy', '2016-01-14'],
            ['Roman Hughes', 'roman.hughes@example.com', '6501241434', 'Stock Clerk', 'New Jersy', '2016-03-08'],
            ['Austin Flores', 'austin.flores@example.com', '6501245234', 'Stock Clerk', 'New Jersy', '2016-08-20'],
            ['Ellis Washington', 'ellis.washington@example.com', '6501246234', 'Stock Clerk', 'New Jersy', '2016-10-30'],
            ['Jamie Butler', 'jamie.butler@example.com', '6501247234', 'Stock Clerk', 'New Jersy', '2016-02-16'],
            ['Isla Graham', 'isla.graham@example.com', '6501212004', 'Stock Clerk', 'New Jersy', '2016-07-09'],
            ['Seth Foster', 'seth.foster@example.com', '6501271934', 'Stock Clerk', 'New Jersy', '2016-06-14'],
            ['Carter Gonzales', 'carter.gonzales@example.com', '6501271834', 'Stock Clerk', 'New Jersy', '2016-08-26'],
            ['Felix Bryant', 'felix.bryant@example.com', '6501271734', 'Stock Clerk', 'New Jersy', '2016-12-12'],
            ['Ibrahim Alexander', 'ibrahim.alexander@example.com', '6501271634', 'Stock Clerk', 'New Jersy', '2016-02-06'],
            ['Sonny Russell', 'sonny.russell@example.com', '6501211234', 'Stock Clerk', 'New Jersy', '2016-07-14'],        ];

        foreach ($data as [$name, $email, $phone, $jobTitle, $warehouseName, $hireDate]) {
            $warehouse = Warehouse::where('name', $warehouseName)->first();

            Employee::updateOrCreate(
                ['email' => $email],
                [
                    'name'         => $name,
                    'email'        => $email,
                    'phone'        => $phone,
                    'job_title'    => $jobTitle,
                    'warehouse_id' => $warehouse?->id,
                    'hire_date'    => $hireDate,
                ]
            );
        }
    }
}
