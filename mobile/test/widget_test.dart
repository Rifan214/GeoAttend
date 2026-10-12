import 'package:flutter_test/flutter_test.dart';
import 'package:geoattend/main.dart';

void main() {
  testWidgets('App smoke test loads Splash/GeoAttendApp without crashes', (WidgetTester tester) async {
    await tester.pumpWidget(const GeoAttendApp());

    // Expect brand name to be rendered
    expect(find.text('GeoAttend'), findsOneWidget);
  });
}
